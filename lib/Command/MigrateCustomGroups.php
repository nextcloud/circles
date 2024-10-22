<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\CirclesManager;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCustomGroups extends Base {
	private OutputInterface $output;
	/** @var IFederatedUser[] */
	private array $fedList = [];

	public function __construct(
		private CirclesManager $circlesManager,
		protected IDBConnection $connection,
		protected IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:migrate:customgroups');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->output = $output;
		if (!$this->shouldRun()) {
			$this->output->writeln('migration already done or table \'custom_group\' not found');
			return 0;
		}

		$this->migrateTeams();
		$this->config->setAppValue('circles', 'imported_custom_groups', 'true');

		return 0;
	}

	public function migrateTeams(): void {
		$this->output->writeln('Migrating custom groups to Teams');

		$owners = $this->extractCustomGroupsAndOwners();

		// we get list of all custom groups
		$queryCustomGroups = $this->connection->getQueryBuilder();
		$queryCustomGroups->select('group_id', 'display_name', 'uri')
						  ->from('custom_group')
						  ->orderBy('group_id');

		$resultCustomGroups = $queryCustomGroups->executeQuery();

		// we cycle for each custom group
		while ($rowCG = $resultCustomGroups->fetch()) {
			$groupId = $rowCG['group_id'] ?? 0;
			$groupUri = $rowCG['uri'] ?? '';
			$ownerId = $owners[$groupId] ?? '';
			if ($ownerId === '' || $groupId === 0) {
				continue; // if group or owner is not know, we ignore the entry.
			}

			$name = $rowCG['display_name'];
			while (strlen($name) < 3) {
				$name = '_' . $name;
			}

			// based on owner's userid, we create federateduser and a new circle
			$this->output->writeln('+ New Team <info>' . $name . '</info>, owner by <info>' . $ownerId . '</info>');
			$owner = $this->cachedFed($ownerId);

			$this->circlesManager->startSession($owner);
			try {
				$circle = $this->circlesManager->createCircle($name);
			} catch (\Exception $e) {
				$this->output->writeln('<error>' . get_class($e) . ' ' . $e->getMessage() . '</error> with data ' . json_encode($rowCG));
				$this->logger->log(2, 'error while creating circle', ['exception' => $e]);
				$this->circlesManager->stopSession();
				continue;
			}

			// we get all members for this custom group
			$queryMembers = $this->connection->getQueryBuilder();
			$queryMembers->select('user_id', 'role')
						 ->from('custom_group_member')
						 ->where($queryMembers->expr()->eq('group_id', $queryMembers->createNamedParameter($groupId)));

			$members = [$ownerId];
			$resultMembers = $queryMembers->executeQuery();
			while ($rowM = $resultMembers->fetch()) {
				$userId = $rowM['user_id'];
				// if admin, ignore
				if ($userId === '') {
					continue;
				}

				try {
					$members[] = $userId;
					if ($userId === $ownerId) {
						continue; // owner is already in the circles
					}

					$this->output->writeln(' - new member <info>' . $userId .'</info>');
					$member = $this->circlesManager->addMember($circle->getSingleId(), $this->cachedFed($userId));
					if ($rowM['role'] === '1') {
						$this->circlesManager->levelMember($member->getId(), Member::LEVEL_ADMIN);
					}
				} catch (\Exception $e) {
					$this->output->writeln('<error>' . get_class($e) . ' ' . $e->getMessage() . '</error>');
					$this->logger->log(2, 'error while migrating custom group member', ['exception' => $e]);
				}
			}

			$this->circlesManager->stopSession();
			$resultMembers->closeCursor();

			$this->updateShares($groupUri, $circle->getSingleId(), $members);
			$this->output->writeln('');
		}

		$resultCustomGroups->closeCursor();
	}

	/**
	 * - type 7 instead of 1
	 * - with circle ID instead of `customgroup_` + group URI
	 * - update children using memberIds
	 *
	 * @param string $groupUri
	 * @param string $circleId
	 * @param array $memberIds
	 *
	 * @throws Exception
	 */
	public function updateShares(string $groupUri, string $circleId, array $memberIds): void {
		$shareIds = $this->getSharedIds($groupUri);

		$update = $this->connection->getQueryBuilder();
		$update->update('share')
			   ->set('share_type', $update->createNamedParameter(IShare::TYPE_CIRCLE))
			   ->set('share_with', $update->createNamedParameter($circleId))
			   ->where($update->expr()->in('id', $update->createNamedParameter($shareIds, IQueryBuilder::PARAM_INT_ARRAY)));

		$count = $update->executeStatement();
		$this->output->writeln('> ' . $count . ' shares updated');

		$this->fixShareChildren($shareIds, $memberIds);
	}

	/**
	 * manage local cache FederatedUser
	 *
	 * @param string $userId
	 * @return FederatedUser
	 */
	private function cachedFed(string $userId): FederatedUser {
		if (!array_key_exists($userId, $this->fedList)) {
			$this->fedList[$userId] = $this->circlesManager->getLocalFederatedUser($userId);
		}

		return $this->fedList[$userId];
	}

	/**
	 * update share children using the correct member id
	 *
	 * @param string $shareId
	 * @param array $memberIds
	 */
	private function fixShareChildren(array $shareIds, array $memberIds): void {
		$update = $this->connection->getQueryBuilder();
		$update->update('share')
			->set('share_type', $update->createNamedParameter(IShare::TYPE_CIRCLE))
			->set('share_with', $update->createParameter('new_recipient'))
			->where($update->expr()->in('parent', $update->createNamedParameter($shareIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($update->expr()->eq('share_with', $update->createParameter('old_recipient')));

		$count = 0;
		foreach ($memberIds as $memberId) {
			$update->setParameter('old_recipient', $memberId);
			$update->setParameter('new_recipient', $this->cachedFed($memberId)->getSingleId());
			$count += $update->executeStatement();
		}

		$this->output->writeln('> ' . $count . ' children shares updated');
	}


	private function getSharedIds(string $groupUri): array {
		$select = $this->connection->getQueryBuilder();
		$select->select('*')
			   ->from('share')
			   ->where($select->expr()->eq('share_type', $select->createNamedParameter(IShare::TYPE_GROUP)));

		$shareIds = [];
		$result = $select->execute();
		while ($row = $result->fetch()) {
			$with = $row['share_with'];
			if (!str_starts_with($with, 'customgroup_')
				|| substr($with, strlen('customgroup_')) !== $groupUri) {
				// not a custom group, or not the one we're looking for
				continue;
			}

			$shareIds[] = $row['id'];
		}

		return $shareIds;
	}

	protected function shouldRun(): bool {
		$alreadyImported = $this->config->getAppValue('circles', 'imported_custom_groups', 'false');
		return $alreadyImported === 'false' && $this->connection->tableExists('custom_group') && $this->connection->tableExists('custom_group_member');
	}

	/**
	 * returns owners for each custom groups
	 *
	 * @return array<string, string> [groupId => userId]
	 * @throws Exception
	 */
	private function extractCustomGroupsAndOwners(): array {
		$queryOwners = $this->connection->getQueryBuilder();
		$queryOwners->select('group_id', 'user_id')
					->from('custom_group_member')
					->where($queryOwners->expr()->eq('role', $queryOwners->createNamedParameter('1')));

		$resultOwners = $queryOwners->executeQuery();
		$owners = [];
		while ($rowO = $resultOwners->fetch()) {
			// no idea if custom groups in owncloud can hold multiple 'owner'
			$owners[$rowO['group_id']] = $owners[$rowO['group_id']] ?? $rowO['user_id'];
		}
		$resultOwners->closeCursor();

		return $owners;
	}
}
