<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class CirclesRequestBuilder extends CoreRequestBuilder {


	/** @var MembersRequest */
	protected $membersRequest;

	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 * @param MembersRequest $membersRequest
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, MembersRequest $membersRequest,
		ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $miscService);
		$this->membersRequest = $membersRequest;
	}


	/**
	 * Left Join the Groups table
	 *
	 * @param IQueryBuilder $qb
	 * @param string $field
	 */
	protected function leftJoinGroups(IQueryBuilder &$qb, $field) {
		$expr = $qb->expr();

		$qb->leftJoin(
			$this->default_select_alias, CoreRequestBuilder::TABLE_GROUPS, 'g',
			$expr->eq($field, 'g.circle_id')
		);
	}

	/**
	 * Limit the search to a non-personal circle
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToNonPersonalCircle(IQueryBuilder &$qb) {
		$expr = $qb->expr();

		$qb->andWhere(
			$expr->neq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL))
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 * @param $userId
	 * @param $type
	 * @param $name
	 *
	 * @throws ConfigNoCircleAvailableException
	 */
	protected function limitRegardingCircleType(
		IQueryBuilder &$qb, $userId, $circleUniqueId, $type, $name
	) {
		$orTypes = $this->generateLimit($qb, $circleUniqueId, $userId, $type, $name);
		if (sizeof($orTypes) === 0) {
			throw new ConfigNoCircleAvailableException(
				$this->l10n->t(
					'You cannot use the Circles Application until your administrator has allowed at least one type of circles'
				)
			);
		}

		$orXTypes = $qb->expr()
					   ->orX();
		foreach ($orTypes as $orType) {
			$orXTypes->add($orType);
		}

		$qb->andWhere($orXTypes);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 * @param $userId
	 * @param $type
	 * @param $name
	 *
	 * @return array
	 */
	private function generateLimit(IQueryBuilder &$qb, $circleUniqueId, $userId, $type, $name) {
		$orTypes = [];
		array_push($orTypes, $this->generateLimitPersonal($qb, $userId, $type));
		array_push($orTypes, $this->generateLimitSecret($qb, $circleUniqueId, $type, $name));
		array_push($orTypes, $this->generateLimitClosed($qb, $type));
		array_push($orTypes, $this->generateLimitPublic($qb, $type));

		return array_filter($orTypes);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int|string $userId
	 * @param int $type
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 */
	private function generateLimitPersonal(IQueryBuilder $qb, $userId, $type) {
		if (!(Circle::CIRCLES_PERSONAL & (int)$type)) {
			return null;
		}
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		return $expr->andX(
			$expr->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL)),
			$expr->eq('o.user_id', $qb->createNamedParameter((string)$userId))
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 * @param int $type
	 * @param string $name
	 *
	 * @return string
	 */
	private function generateLimitSecret(IQueryBuilder $qb, $circleUniqueId, $type, $name) {
		if (!(Circle::CIRCLES_SECRET & (int)$type)) {
			return null;
		}
		$expr = $qb->expr();

		$orX = $expr->orX($expr->gte('u.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)));
		$orX->add($expr->eq('c.name', $qb->createNamedParameter($name)))
			->add(
				$expr->eq(
					$qb->createNamedParameter($circleUniqueId),
					$qb->createFunction(
						'SUBSTR(`c`.`unique_id`, 1, ' . Circle::SHORT_UNIQUE_ID_LENGTH . ')'
					)
				)
			);

		if ($this->leftJoinedNCGroupAndUser) {
			$orX->add($expr->gte('g.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)));
		}

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$sqb = $expr->andX(
			$expr->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_SECRET)),
			$expr->orX($orX)
		);

		return $sqb;
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 *
	 * @return string
	 */
	private function generateLimitClosed(IQueryBuilder $qb, $type) {
		if (!(Circle::CIRCLES_CLOSED & (int)$type)) {
			return null;
		}

		return $qb->expr()
				  ->eq(
					  'c.type',
					  $qb->createNamedParameter(Circle::CIRCLES_CLOSED)
				  );
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 *
	 * @return string
	 */
	private function generateLimitPublic(IQueryBuilder $qb, $type) {
		if (!(Circle::CIRCLES_PUBLIC & (int)$type)) {
			return null;
		}

		return $qb->expr()
				  ->eq(
					  'c.type',
					  $qb->createNamedParameter(Circle::CIRCLES_PUBLIC)
				  );
	}


	/**
	 * add a request to the members list, using the current user ID.
	 * will returns level and stuff.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	public function leftJoinUserIdAsViewer(IQueryBuilder &$qb, $userId) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = '`' . $this->default_select_alias . '`.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('u.user_id', 'viewer_userid')
		   ->selectAlias('u.status', 'viewer_status')
		   ->selectAlias('u.level', 'viewer_level')
		   ->leftJoin(
			   $this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'u',
			   $expr->andX(
				   $expr->eq(
					   'u.circle_id',
					   $qb->createFunction(
						   'SUBSTR(' . $pf . '`unique_id`, 1, ' . Circle::SHORT_UNIQUE_ID_LENGTH
						   . ')'
					   )
				   ),
				   $expr->eq('u.user_id', $qb->createNamedParameter($userId)),
				   $expr->eq('u.user_type', $qb->createNamedParameter(Member::TYPE_USER))
			   )
		   );
	}


	/**
	 * Left Join members table to get the owner of the circle.
	 *
	 * @param IQueryBuilder $qb
	 */
	public function leftJoinOwner(IQueryBuilder &$qb) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = '`' . $this->default_select_alias . '`.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('o.user_id', 'owner_userid')
		   ->selectAlias('o.status', 'owner_status')
		   ->selectAlias('o.level', 'owner_level')
		   ->leftJoin(
			   $this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'o',
			   $expr->andX(
				   $expr->eq(
					   $qb->createFunction(
						   'SUBSTR(' . $pf . '`unique_id`, 1, ' . Circle::SHORT_UNIQUE_ID_LENGTH
						   . ')'
					   )
					   , 'o.circle_id'
				   ),
				   $expr->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER)),
				   $expr->eq('o.user_type', $qb->createNamedParameter(Member::TYPE_USER))
			   )
		   );
	}



	/**
	 * Base of the Sql Insert request for Shares
	 *
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_CIRCLES);

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param int $uniqueId
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesUpdateSql($uniqueId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_CIRCLES)
		   ->where(
			   $qb->expr()
				  ->eq('unique_id', $qb->createNamedParameter($uniqueId))
		   );

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @param string $circleUniqueId
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesDeleteSql($circleUniqueId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_CIRCLES)
		   ->where(
			   $qb->expr()
				  ->eq(
					  $qb->createFunction(
						  'SUBSTR(`unique_id`, 1, ' . Circle::SHORT_UNIQUE_ID_LENGTH . ')'
					  ),
					  $qb->createNamedParameter($circleUniqueId)
				  )
		   );

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
			   'c.id', 'c.name', 'c.description', 'c.settings', 'c.type', 'c.creation'
		   )
		   ->from(CoreRequestBuilder::TABLE_CIRCLES, 'c');
		$this->default_select_alias = 'c';

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 */
	protected function parseCirclesSelectSql($data) {

		$circle = new Circle();
		$circle->setId($data['id']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setName($data['name']);
		$circle->setDescription($data['description']);
		$circle->setSettings($data['settings']);
		$circle->setType($data['type']);
		$circle->setCreation($data['creation']);

		if (key_exists('viewer_level', $data)) {
			$user = new Member($data['viewer_userid'], Member::TYPE_USER, $circle->getUniqueId());
			$user->setStatus($data['viewer_status']);
			$user->setLevel($data['viewer_level']);
			$circle->setViewer($user);
		}

		if (key_exists('owner_level', $data)) {
			$owner = new Member($data['owner_userid'], Member::TYPE_USER, $circle->getUniqueId());
			$owner->setStatus($data['owner_status']);
			$owner->setLevel($data['owner_level']);
			$circle->setOwner($owner);
		}

		return $circle;
	}


}