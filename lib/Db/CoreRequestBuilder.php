<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 7/4/17
 * Time: 5:01 PM
 */

namespace OCA\Circles\Db;


use OCP\DB\QueryBuilder\IQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use OC\L10N\L10N;
use OCA\Circles\Service\MiscService;
use OCP\IDBConnection;

class CoreRequestBuilder {

	const TABLE_CIRCLES = 'circles_circles';
	const TABLE_MEMBERS = 'circles_members';
	const TABLE_GROUPS = 'circles_groups';
	const TABLE_SHARES = 'circles_shares';
	const TABLE_LINKS = 'circles_links';

	const NC_TABLE_GROUP_USER = 'group_user';

	/** @var IDBConnection */
	protected $dbConnection;

	/** @var L10N */
	protected $l10n;

	/** @var MiscService */
	protected $miscService;

	/** @var string */
	protected $default_select_alias;


	/**
	 * RequestBuilder constructor.
	 *
	 * @param L10N $l10n
	 * @param IDBConnection $connection
	 * @param MiscService $miscService
	 */
	public function __construct(L10N $l10n, IDBConnection $connection, MiscService $miscService) {
		$this->l10n = $l10n;
		$this->dbConnection = $connection;
		$this->miscService = $miscService;
	}


	/**
	 * Limit the request by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder &$qb, $id) {
		$this->limitToDBField($qb, 'id', $id);
	}


	/**
	 * Limit the request by its UniqueId.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $uniqueId
	 */
	protected function limitToUniqueId(IQueryBuilder &$qb, $uniqueId) {
		$this->limitToDBField($qb, 'unique_id', $uniqueId);
	}


	/**
	 * Limit the request by its Token.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $token
	 */
	protected function limitToToken(IQueryBuilder &$qb, $token) {
		$this->limitToDBField($qb, 'token', $token);
	}


	/**
	 * Limit the request to the User by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 *
	 * @internal param int $circleId
	 */
	protected function limitToUserId(IQueryBuilder &$qb, $userId) {
		$this->limitToDBField($qb, 'user_id', $userId);
	}


	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 */
	protected function limitToCircleId(IQueryBuilder &$qb, $circleUniqueId) {
		$this->limitToDBField($qb, 'circle_id', $circleUniqueId);
	}


	/**
	 * Limit the request to the Group by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $groupId
	 */
	protected function limitToGroupId(IQueryBuilder &$qb, $groupId) {
		$this->limitToDBField($qb, 'group_id', $groupId);
	}


	/**
	 * Limit the search by its Name
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToName(IQueryBuilder &$qb, $name) {
		$this->limitToDBField($qb, 'name', $name);
	}


	/**
	 * Limit the request to a minimum member level.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $level
	 * @param string|array $pf
	 */
	protected function limitToLevel(IQueryBuilder &$qb, $level, $pf = '') {
		$expr = $qb->expr();
		$orX = $expr->orX();

		if ($pf === '') {
			$p = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
			$orX->add($expr->gte($p . 'level', $qb->createNamedParameter($level)));

		} else {

			if (!is_array($pf)) {
				$pf = [$pf];
			}

			foreach ($pf as $p) {
				$orX->add($expr->gte($p . '.level', $qb->createNamedParameter($level)));
			}
		}

		$qb->andWhere($orX);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer $value
	 */
	private function limitToDBField(IQueryBuilder & $qb, $field, $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
		$qb->andWhere($expr->eq($pf . $field, $qb->createNamedParameter($value)));
	}


	/**
	 * link to the groupId/UserId of the NC DB.
	 * If userId is empty, we add the uid of the NCGroup Table in the select list with 'user_id'
	 * alias
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function limitToNCGroupUser(IQueryBuilder $qb, $userId = '') {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$and = $expr->andX($expr->eq($pf . 'group_id', 'ncgu.gid'));
		if ($userId !== '') {
			$and->add($expr->eq('ncgu.uid', $qb->createNamedParameter($userId)));
		} else {
			$qb->selectAlias('ncgu.uid', 'user_id');
		}

		$qb->from(self::NC_TABLE_GROUP_USER, 'ncgu');
		$qb->andWhere($and);
	}


	/**
	 * Right Join the Circles table
	 *
	 * @param IQueryBuilder $qb
	 *
	 * @deprecated not used (14/07/17)
	 */
	protected function rightJoinCircles(& $qb) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$qb->from(self::TABLE_CIRCLES, 'c')
		   ->andWhere($expr->eq('c.unique_id', $pf . 'circle_id'));
	}


	/**
	 * link to the groupId/UserId of the NC DB.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param string $field
	 */
	protected function leftJoinNCGroupAndUser(IQueryBuilder $qb, $userId, $field) {
		$expr = $qb->expr();

		$qb->leftJoin(
			$this->default_select_alias, self::NC_TABLE_GROUP_USER, 'ncgu',
			$expr->eq('ncgu.uid', $qb->createNamedParameter($userId))
		);

		$qb->leftJoin(
			$this->default_select_alias, CoreRequestBuilder::TABLE_GROUPS, 'g',
			$expr->andX(
				$expr->eq('ncgu.gid', 'g.group_id'),
				$expr->eq($field, 'g.circle_id')
			)
		);
	}
}