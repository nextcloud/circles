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
use OC\L10N\L10N;
use OCA\Circles\Exceptions\ConfigNoCircleAvailable;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

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
		L10N $l10n, IDBConnection $connection, MembersRequest $membersRequest,
		MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $miscService);
		$this->membersRequest = $membersRequest;
	}


	/**
	 * Join the Circles table
	 *
	 * @param IQueryBuilder $qb
	 * @param string $field
	 */
	protected function joinCircles(& $qb, $field) {
		$expr = $qb->expr();

		$qb->from(self::TABLE_CIRCLES, 'c')
		   ->andWhere($expr->eq('c.id', $field));
	}


	/**
	 * Join the Circles table
	 *
	 * @param IQueryBuilder $qb
	 * @param string $field
	 */
	protected function leftJoinGroups(& $qb, $field) {
		$expr = $qb->expr();

		$qb->leftJoin(
			$this->default_select_alias, CoreRequestBuilder::TABLE_GROUPS, 'g',
			$expr->eq($field, 'g.circle_id')
		);
	}

//
//	/**
//	 * Link to member (userId) of circle
//	 *
//	 * @param IQueryBuilder $qb
//	 * @param string $field
//	 */
//	protected function leftJoinMembers(& $qb, $field) {
//		$expr = $qb->expr();
//
//		$qb->leftJoin(
//			$this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'm',
//			$expr->eq('m.circle_id', $field)
//		);
////		$qb->from(self::TABLE_MEMBERS, 'm')
////		   ->andWhere($expr->eq('m.circle_id', $field));
//	}



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
	 * @param $circleId
	 * @param $userId
	 * @param $type
	 * @param $name
	 *
	 * @throws ConfigNoCircleAvailable
	 */
	protected function limitRegardingCircleType(IQueryBuilder &$qb, $userId, $circleId, $type, $name
	) {
		$orTypes = $this->generateLimit($qb, $circleId, $userId, $type, $name);
		if (sizeof($orTypes) === 0) {
			throw new ConfigNoCircleAvailable(
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
	 * @param $circleId
	 * @param $userId
	 * @param $type
	 * @param $name
	 *
	 * @return array
	 */
	private function generateLimit(IQueryBuilder &$qb, $circleId, $userId, $type, $name) {
		$orTypes = [];
		array_push($orTypes, $this->generateLimitPersonal($qb, $userId, $type));
		array_push($orTypes, $this->generateLimitHidden($qb, $circleId, $type, $name));
		array_push($orTypes, $this->generateLimitPrivate($qb, $type));
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
	 * @param int $circleId
	 * @param int $type
	 * @param string $name
	 *
	 * @return string
	 */
	private function generateLimitHidden(IQueryBuilder $qb, $circleId, $type, $name) {
		if (!(Circle::CIRCLES_HIDDEN & (int)$type)) {
			return null;
		}
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$sqb = $expr->andX(
			$expr->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_HIDDEN)),
			$expr->orX(
				$expr->gte(
					'u.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)
				),
				$expr->gte(
					'g.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)
				),
				// TODO: Replace search on CircleID By a search on UniqueID
				$expr->eq('c.id', $qb->createNamedParameter($circleId)),
				$expr->eq('c.name', $qb->createNamedParameter($name))
			)
		);

		return $sqb;
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 *
	 * @return string
	 */
	private function generateLimitPrivate(IQueryBuilder $qb, $type) {
		if (!(Circle::CIRCLES_PRIVATE & (int)$type)) {
			return null;
		}

		return $qb->expr()
				  ->eq(
					  'c.type',
					  $qb->createNamedParameter(Circle::CIRCLES_PRIVATE)
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
	 * @param Circle $circle
	 *
	 * @deprecated
	 *
	 * do nothing.
	 */
	protected function filterCircleRegardingViewer(Circle $circle) {
//		if ($circle->getHigherViewer()
//				   ->getLevel() < Member::LEVEL_MODERATOR
//		) {
//		}
//		$members = $circle->getMembers();
//
//		foreach ($members as $member) {
//			$member->setNote('ok');
//		}
////			$circle->setMembers($members);
//
//
//		foreach ($members as $member) {
//			$this->miscService->log('note: ' . $member->getNote());
//		}


	}

	/**
	 * add a request to the members list, using the current user ID.
	 * will returns level and stuff.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function leftJoinUserIdAsViewer(IQueryBuilder & $qb, $userId) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('u.user_id', 'viewer_userid')
		   ->selectAlias('u.status', 'viewer_status')
		   ->selectAlias('u.level', 'viewer_level')
		   ->leftJoin(
			   $this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'u',
			   $expr->andX(
				   $expr->eq($pf . 'id', 'u.circle_id'),
				   $expr->eq('u.user_id', $qb->createNamedParameter($userId))
			   )
		   );
	}

	/**
	 * Left Join members table to get the owner of the circle.
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function leftJoinOwner(IQueryBuilder & $qb) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('o.user_id', 'owner_userid')
		   ->selectAlias('o.status', 'owner_status')
		   ->selectAlias('o.level', 'owner_level')
		   ->leftJoin(
			   $this->default_select_alias, CoreRequestBuilder::TABLE_MEMBERS, 'o',
			   $expr->andX(
				   $expr->eq($pf . 'id', 'o.circle_id'),
				   $expr->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
			   )
		   );
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getLinksSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('id', 'status', 'address', 'token', 'circle_id', 'unique_id', 'creation')
		   ->from(self::TABLE_LINKS, 's');

		$this->default_select_alias = 's';

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'circle_id', 'source', 'type', 'author', 'cloud_id', 'payload', 'creation', 'headers',
			'unique_id'
		)
		   ->from(self::TABLE_SHARES, 's');

		$this->default_select_alias = 's';

		return $qb;
	}

	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_SHARES)
		   ->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param string $uniqueId
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesUpdateSql($uniqueId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_SHARES)
		   ->where(
			   $qb->expr()
				  ->eq('unique_id', $qb->createNamedParameter((string)$uniqueId))
		   );

		return $qb;
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_CIRCLES)
		   ->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Shares
	 *
	 * @param int $circleId
	 *
	 * @return IQueryBuilder
	 */
	protected function getCirclesUpdateSql($circleId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_CIRCLES)
		   ->where(
			   $qb->expr()
				  ->eq('id', $qb->createNamedParameter($circleId))
		   );

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('m.user_id', 'm.circle_id', 'm.level', 'm.status', 'm.joined')
		   ->from(self::TABLE_MEMBERS, 'm');

		$this->default_select_alias = 'm';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getCirclesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb
			->selectDistinct('c.unique_id')
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
	 * @return Member
	 */
	protected function parseMembersSelectSql(array $data) {
		$member = new Member($this->l10n);
		$member->setUserId($data['user_id']);
		$member->setCircleId($data['circle_id']);
		$member->setLevel($data['level']);
		$member->setStatus($data['status']);
		$member->setJoined($data['joined']);

		return $member;
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 */
	protected function parseCirclesSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$circle = new Circle($this->l10n);
		$circle->setId($data['id']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setName($data['name']);
		$circle->setDescription($data['description']);
		$circle->setSettings($data['settings']);
		$circle->setType($data['type']);
		$circle->setCreation($data['creation']);

		if (key_exists('viewer_level', $data)) {
			$user = new Member($this->l10n);
			$user->setStatus($data['viewer_status']);
			$user->setCircleId($circle->getId());
			$user->setUserId($data['viewer_userid']);
			$user->setLevel($data['viewer_level']);
			$circle->setViewer($user);
		}

		if (key_exists('owner_level', $data)) {
			$owner = new Member($this->l10n);
			$owner->setStatus($data['owner_status']);
			$owner->setCircleId($circle->getId());
			$owner->setUserId($data['owner_userid']);
			$owner->setLevel($data['owner_level']);
			$circle->setOwner($owner);
		}

//		if (key_exists('group_level', $data))
//		{
//			$group = new Member($this->l10n);
//			$group->setGroupId($data['group_id']);
//			$group->setLevel($data['group_level']);
//			$circle->setGroupViewer($group);
//		}

		return $circle;
	}


	/**
	 * @param array $data
	 *
	 * @return SharingFrame
	 */
	protected function parseSharesSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$frame = new SharingFrame($data['source'], $data['type']);
		$frame->setCircleId($data['circle_id']);
		$frame->setAuthor($data['author']);
		$frame->setCloudId($data['cloud_id']);
		$frame->setPayload(json_decode($data['payload'], true));
		$frame->setCreation($data['creation']);
		$frame->setHeaders(json_decode($data['headers'], true));
		$frame->setUniqueId($data['unique_id']);

		return $frame;
	}


	/**
	 * @param array $data
	 *
	 * @return FederatedLink
	 */
	public function parseLinksSelectSql($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$link = new FederatedLink();
		$link->setId($data['id'])
			 ->setUniqueId($data['unique_id'])
			 ->setStatus($data['status'])
			 ->setCreation($data['creation'])
			 ->setAddress($data['address'])
			 ->setToken($data['token'])
			 ->setCircleId($data['circle_id']);

		return $link;
	}


}