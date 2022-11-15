<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Db;

use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class DeprecatedCirclesRequestBuilder extends DeprecatedRequestBuilder {
	/** @var DeprecatedMembersRequest */
	protected $membersRequest;

	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 * @param DeprecatedMembersRequest $membersRequest
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, DeprecatedMembersRequest $membersRequest,
		ConfigService $configService, TimezoneService $timezoneService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $timezoneService, $miscService);
		$this->membersRequest = $membersRequest;
	}


	/**
	 * Limit the search to a non-personal circle
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToNonPersonalCircle(IQueryBuilder $qb) {
		$expr = $qb->expr();

		$qb->andWhere(
			$expr->neq('c.type', $qb->createNamedParameter(DeprecatedCircle::CIRCLES_PERSONAL))
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param string $circleUniqueId
	 * @param $type
	 * @param $name
	 * @param bool $forceAll
	 *
	 * @deprecated
	 * @throws ConfigNoCircleAvailableException
	 */
	protected function limitRegardingCircleType(
		IQueryBuilder $qb, string $userId, $circleUniqueId, int $type,
		string $name, bool $forceAll = false
	) {
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 * @param $userId
	 * @param $type
	 * @param $name
	 * @param bool $forceAll
	 *
	 * @return array
	 */
	private function generateLimit(
		IQueryBuilder $qb, $circleUniqueId, $userId, $type, $name, $forceAll = false
	) {
		return [];
	}


	/**
	 * add a request to the members list, using the current user ID.
	 * will returns level and stuff.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param int $type
	 * @param string $instanceId
	 */
	public function leftJoinUserIdAsViewer(IQueryBuilder $qb, string $userId, int $type, string $instanceId
	) {
		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = '' . $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('u.user_id', 'viewer_userid')
		   ->selectAlias('u.user_type', 'viewer_type')
		   ->selectAlias('u.instance', 'viewer_instance')
		   ->selectAlias('u.status', 'viewer_status')
		   ->selectAlias('u.member_id', 'viewer_member_id')
		   ->selectAlias('u.cached_name', 'viewer_cached_name')
		   ->selectAlias('u.cached_update', 'viewer_cached_update')
		   ->selectAlias('u.level', 'viewer_level')
		   ->leftJoin(
		   	$this->default_select_alias, DeprecatedRequestBuilder::TABLE_MEMBERS, 'u',
		   	$expr->andX(
		   		$expr->eq('u.circle_id', $pf . 'unique_id'),
		   		$expr->eq('u.user_id', $qb->createNamedParameter($userId)),
		   		$expr->eq('u.instance', $qb->createNamedParameter($instanceId)),
		   		$expr->eq('u.user_type', $qb->createNamedParameter($type))
		   	)
		   );
	}


	/**
	 * Left Join members table to get the owner of the circle.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $ownerId
	 */
	public function leftJoinOwner(IQueryBuilder $qb, string $ownerId = '') {
		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('o.user_id', 'owner_userid')
		   ->selectAlias('o.member_id', 'owner_member_id')
		   ->selectAlias('o.instance', 'owner_instance')
		   ->selectAlias('o.cached_name', 'owner_cached_name')
		   ->selectAlias('o.cached_update', 'owner_cached_update')
		   ->selectAlias('o.status', 'owner_status')
		   ->selectAlias('o.level', 'owner_level')
		   ->leftJoin(
		   	$this->default_select_alias, DeprecatedRequestBuilder::TABLE_MEMBERS, 'o',
		   	$expr->andX(
		   		$expr->eq('o.circle_id', $pf . 'unique_id'),
		   		$expr->eq('o.level', $qb->createNamedParameter(DeprecatedMember::LEVEL_OWNER)),
		   		$expr->eq('o.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER))
		   	)
		   );

		if ($ownerId !== '') {
			$qb->andWhere($expr->eq('o.user_id', $qb->createNamedParameter($ownerId)));
		}
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_CIRCLES)
		   ->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}



	/**
	 * @return IQueryBuilder
	 */
	protected function getCirclesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectDistinct('c.unique_id')
		   ->addSelect(
		   	'c.id', 'c.name', 'c.alt_name', 'c.description', 'c.settings', 'c.type', 'contact_addressbook',
		   	'contact_groupname', 'c.creation'
		   )
		   ->from(DeprecatedRequestBuilder::TABLE_CIRCLES, 'c');
		$this->default_select_alias = 'c';

		return $qb;
	}


	/**
	 * @param array $data
	 * @param bool $allSettings
	 *
	 * @return DeprecatedCircle
	 */
	protected function parseCirclesSelectSql($data, bool $allSettings = false) {
		$circle = new DeprecatedCircle();
		$circle->setId($data['id']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setName($data['name']);
		$circle->setAltName($data['alt_name']);
		$circle->setDescription($data['description']);
		if ($data['contact_addressbook'] !== null) {
			$circle->setContactAddressBook($data['contact_addressbook']);
		}
		if ($data['contact_groupname'] !== null) {
			$circle->setContactGroupName($data['contact_groupname']);
		}
		$circle->setSettings($data['settings'], $allSettings);
		$circle->setType($data['type']);
		$circle->setCreation($data['creation']);

		if (key_exists('viewer_level', $data)) {
			$user = new DeprecatedMember(
				$data['viewer_userid'], DeprecatedMember::TYPE_USER, $circle->getUniqueId()
			);
			$user->setStatus($data['viewer_status']);
			$user->setMemberId($data['viewer_member_id']);
			$user->setCachedName($data['viewer_cached_name']);
			$user->setType($data['viewer_type']);
			$user->setInstance($data['viewer_instance']);
			$user->setLevel($data['viewer_level']);
			$circle->setViewer($user);
		}

		if (key_exists('owner_level', $data)) {
			$owner = new DeprecatedMember(
				$data['owner_userid'], DeprecatedMember::TYPE_USER, $circle->getUniqueId()
			);
			$owner->setCachedName($data['owner_cached_name']);
			$owner->setMemberId($data['owner_member_id']);
			$owner->setStatus($data['owner_status']);
			$owner->setInstance($data['owner_instance']);
			$owner->setLevel($data['owner_level']);
			$circle->setOwner($owner);
		}

		return $circle;
	}
}
