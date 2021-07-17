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

namespace OCA\Circles\Command;

use Exception;
use OC\Core\Command\Base;
use OC\Share\Share;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedRequestBuilder;
use OCA\Circles\Model\DeprecatedCircle;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixUniqueId extends Base {

	/** @var IDBConnection */
	protected $connection;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	public function __construct(DeprecatedCirclesRequest $circlesRequest, IDBConnection $connection) {
		parent::__construct();
		$this->circlesRequest = $circlesRequest;
		$this->connection = $connection;
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:fixuniqueid')
			 ->setDescription('fix Unique Id issue.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$this->swapToShortenUniqueId();

			$output->writeln('done');
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
		}
	}


	private function swapToShortenUniqueId() {
		$qb = $this->connection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('id', 'unique_id')
		   ->from(DeprecatedRequestBuilder::TABLE_CIRCLES);

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circleId = $data['id'];

			$shortenUniqueId = substr($data['unique_id'], 0, DeprecatedCircle::SHORT_UNIQUE_ID_LENGTH);

//			$this->swapToShortenUniqueIdInTable(
//				$circleId, $shortenUniqueId, CoreQueryBuilder::TABLE_GROUPS
//			);
			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, DeprecatedRequestBuilder::TABLE_LINKS
			);
//
//			$this->cleanBuggyDuplicateEntries(
//				$circleId, $shortenUniqueId, CoreQueryBuilder::TABLE_MEMBERS, 'user_id'
//			);

			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, DeprecatedRequestBuilder::TABLE_MEMBERS
			);

			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, DeprecatedRequestBuilder::TABLE_LINKS
			);

			$this->swapToShortenUniqueIdInShares($circleId, $shortenUniqueId);
		}
		$cursor->closeCursor();
	}


	private function swapToShortenUniqueIdInTable($circleId, $shortenUniqueId, $table) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update($table)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		$qb->set('circle_id', $qb->createNamedParameter($shortenUniqueId));
		$qb->execute();
	}


	private function swapToShortenUniqueIdInShares($circleId, $shortenUniqueId) {
		$qb = $this->connection->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update('share')
		   ->where(
			   $expr->andX(
				   $expr->eq(
					   'share_type', $qb->createNamedParameter(Share::SHARE_TYPE_CIRCLE)
				   ),
				   $expr->eq('share_with', $qb->createNamedParameter($circleId))
			   )
		   );

		$qb->set('share_with', $qb->createNamedParameter($shortenUniqueId));
		$qb->execute();
	}
}
