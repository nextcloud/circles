<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 7/4/17
 * Time: 5:01 PM
 */

namespace OCA\Circles\Db;

use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class DeprecatedRequestBuilder {
	public const TABLE_FILE_SHARES = 'share';
	public const SHARE_TYPE = 7;

	public const TABLE_CIRCLES = 'circle_circles';
	public const TABLE_MEMBERS = 'circle_members';
	public const TABLE_GROUPS = 'circle_groups';
	public const TABLE_SHARES = 'circle_shares';
	public const TABLE_LINKS = 'circle_links';
	public const TABLE_TOKENS = 'circle_tokens';
	public const TABLE_GSEVENTS = 'circle_gsevents';
	public const TABLE_GSSHARES = 'circle_gsshares';
	public const TABLE_GSSHARES_MOUNTPOINT = 'circle_gsshares_mp';
	public const TABLE_REMOTE = 'circle_remotes';

	public const NC_TABLE_ACCOUNTS = 'accounts';
	public const NC_TABLE_GROUP_USER = 'group_user';

	/** @var array */
	private $tables = [
		self::TABLE_CIRCLES,
		self::TABLE_GROUPS,
		self::TABLE_MEMBERS,
		self::TABLE_SHARES,
		self::TABLE_LINKS,
		self::TABLE_TOKENS,
		self::TABLE_GSEVENTS,
		self::TABLE_GSSHARES,
		self::TABLE_GSSHARES_MOUNTPOINT
	];


	/** @var IDBConnection */
	protected $dbConnection;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var TimezoneService */
	protected $timezoneService;

	/** @var MiscService */
	protected $miscService;

	/** @var string */
	protected $default_select_alias;

	/** @var bool */
	protected $leftJoinedNCGroupAndUser = false;


	/**
	 * CoreQueryBuilder constructor.
	 *
	 * @param IL10N $l10n
	 * @param IDBConnection $connection
	 * @param ConfigService $configService
	 * @param TimezoneService $timezoneService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, ConfigService $configService,
		TimezoneService $timezoneService, MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->dbConnection = $connection;
		$this->configService = $configService;
		$this->timezoneService = $timezoneService;
		$this->miscService = $miscService;
	}


	/**
	 * Limit the request by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder $qb, $id) {
		$this->limitToDBField($qb, 'id', $id);
	}


	/**
	 * Limit the request by its UniqueId.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $uniqueId
	 */
	protected function limitToUniqueId(IQueryBuilder $qb, $uniqueId) {
		$this->limitToDBField($qb, 'unique_id', $uniqueId);
	}


	/**
	 * Limit the request by its addressbookId.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $bookId
	 */
	protected function limitToAddressBookId(IQueryBuilder $qb, $bookId) {
		$this->limitToDBField($qb, 'contact_addressbook', (string)$bookId);
	}


	/**
	 * Limit the request by its addressbookId.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $groupName
	 */
	protected function limitToContactGroup(IQueryBuilder $qb, $groupName) {
		$this->limitToDBField($qb, 'contact_groupname', $groupName);
	}


	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $contactId
	 */
	protected function limitToContactId(IQueryBuilder $qb, $contactId) {
		$this->limitToDBField($qb, 'contact_id', $contactId);
	}


	/**
	 * Limit the request by its Token.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $token
	 */
	protected function limitToToken(IQueryBuilder $qb, $token) {
		$this->limitToDBField($qb, 'token', $token);
	}


	/**
	 * Limit the request to the User by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $userId
	 */
	protected function limitToUserId(IQueryBuilder $qb, $userId) {
		$this->limitToDBField($qb, 'user_id', $userId);
	}


	/**
	 * Limit the request to the owner
	 *
	 * @param IQueryBuilder $qb
	 * @param $owner
	 */
	protected function limitToOwner(IQueryBuilder $qb, $owner) {
		$this->limitToDBField($qb, 'owner', $owner);
	}


	/**
	 * Limit the request to the Member by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $memberId
	 */
	protected function limitToMemberId(IQueryBuilder $qb, string $memberId) {
		$this->limitToDBField($qb, 'member_id', $memberId);
	}


	/**
	 * Limit the request to the Type entry.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $type
	 */
	protected function limitToUserType(IQueryBuilder $qb, $type) {
		$this->limitToDBField($qb, 'user_type', $type);
	}


	/**
	 * Limit the request to the Instance.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $instance
	 */
	protected function limitToInstance(IQueryBuilder $qb, string $instance) {
		$this->limitToDBField($qb, 'instance', $instance);
	}


	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $circleUniqueId
	 */
	protected function limitToCircleId(IQueryBuilder $qb, $circleUniqueId) {
		$this->limitToDBField($qb, 'circle_id', $circleUniqueId);
	}


	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $shareId
	 */
	protected function limitToShareId(IQueryBuilder $qb, int $shareId) {
		$this->limitToDBField($qb, 'share_id', $shareId);
	}


	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $mountpoint
	 */
	protected function limitToMountpoint(IQueryBuilder $qb, string $mountpoint) {
		$this->limitToDBField($qb, 'share_id', $mountpoint);
	}

	/**
	 * Limit the request to the Circle by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $hash
	 */
	protected function limitToMountpointHash(IQueryBuilder $qb, string $hash) {
		$this->limitToDBField($qb, 'share_id', $hash);
	}

//
//	/**
//	 * Limit the request to the Circle by its Shorten Unique Id.
//	 *
//	 * @param IQueryBuilder $qb
//	 * @param string $circleUniqueId
//	 * @param $length
//	 */
//	protected function limitToShortenUniqueId(IQueryBuilder $qb, $circleUniqueId, $length) {
//		$expr = $qb->expr();
//		$pf = ($qb->getType() === QueryBuilder::SELECT) ? '`' . $this->default_select_alias . '`.' : '';
//
//		$qb->andWhere(
//			$expr->eq(
//				$qb->createNamedParameter($circleUniqueId),
//				$qb->createFunction('SUBSTR(' . $pf . '`unique_id`' . ', 1, ' . $length . ')')
//			)
//		);
//
//	}


	/**
	 * Limit the request to the Group by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $groupId
	 */
	protected function limitToGroupId(IQueryBuilder $qb, $groupId) {
		$this->limitToDBField($qb, 'group_id', $groupId);
	}


	/**
	 * Limit the search by its Name
	 *
	 * @param IQueryBuilder $qb
	 * @param string $name
	 */
	protected function limitToName(IQueryBuilder $qb, $name) {
		$this->limitToDBField($qb, 'name', $name);
	}


	/**
	 * Limit the search by its Status (or greater)
	 *
	 * @param IQueryBuilder $qb
	 * @param string $name
	 */
	protected function limitToStatus(IQueryBuilder $qb, $name) {
		$this->limitToDBFieldOrGreater($qb, 'status', $name);
	}


	/**
	 * Limit the request by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $type
	 */
	protected function limitToShareType(IQueryBuilder $qb, string $type) {
		$this->limitToDBField($qb, 'share_type', $type);
	}


	/**
	 * Limit the request by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $with
	 */
	protected function limitToShareWith(IQueryBuilder $qb, string $with) {
		$this->limitToDBField($qb, 'share_with', $with);
	}


	/**
	 * Limit the request to a minimum member level.
	 *
	 * if $pf is an array, will generate an SQL OR request to limit level in multiple tables
	 *
	 * @param IQueryBuilder $qb
	 * @param int $level
	 * @param string|array $pf
	 */
	protected function limitToLevel(IQueryBuilder $qb, int $level, $pf = '') {
		$expr = $qb->expr();

		if ($pf === '') {
			$p = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
			$qb->andWhere($expr->gte($p . 'level', $qb->createNamedParameter($level)));

			return;
		}

		if (!is_array($pf)) {
			$pf = [$pf];
		}

		$orX = $this->generateLimitToLevelMultipleTableRequest($qb, $level, $pf);
		$qb->andWhere($orX);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $level
	 * @param array $pf
	 *
	 * @return mixed
	 */
	private function generateLimitToLevelMultipleTableRequest(IQueryBuilder $qb, int $level, $pf) {
		$expr = $qb->expr();
		$orX = $expr->orX();

		foreach ($pf as $p) {
			if ($p === 'g' && !$this->leftJoinedNCGroupAndUser) {
				continue;
			}
			$orX->add($expr->gte($p . '.level', $qb->createNamedParameter($level)));
		}

		return $orX;
	}


	/**
	 * Limit the search to Members and Almost members
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToMembersAndAlmost(IQueryBuilder $qb) {
		$expr = $qb->expr();

		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$orX = $expr->orX();
		$orX->add($expr->eq($pf . 'status', $qb->createNamedParameter(DeprecatedMember::STATUS_MEMBER)));
		$orX->add($expr->eq($pf . 'status', $qb->createNamedParameter(DeprecatedMember::STATUS_INVITED)));
		$orX->add($expr->eq($pf . 'status', $qb->createNamedParameter(DeprecatedMember::STATUS_REQUEST)));

		$qb->andWhere($orX);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer $value
	 */
	public function limitToDBField(IQueryBuilder $qb, $field, $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
		$qb->andWhere($expr->eq($pf . $field, $qb->createNamedParameter($value)));
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer $value
	 */
	private function limitToDBFieldOrGreater(IQueryBuilder $qb, $field, $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
		$qb->andWhere($expr->gte($pf . $field, $qb->createNamedParameter($value)));
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

		$and = $expr->andX($expr->eq($pf . 'user_id', 'ncgu.gid'));
		if ($userId !== '') {
			$and->add($expr->eq('ncgu.uid', $qb->createNamedParameter($userId)));
		} else {
			$qb->selectAlias('ncgu.uid', 'user_id');
		}

		$qb->from(self::NC_TABLE_GROUP_USER, 'ncgu');
		$qb->andWhere($and);
	}


	/**
	 * Left Join circle table to get more information about the circle.
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function leftJoinCircle(IQueryBuilder $qb) {
		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('lc.type', 'circle_type')
		   ->selectAlias('lc.name', 'circle_name')
		   ->selectAlias('lc.alt_name', 'circle_alt_name')
		   ->selectAlias('lc.settings', 'circle_settings')
		   ->leftJoin(
		   	$this->default_select_alias, DeprecatedRequestBuilder::TABLE_CIRCLES, 'lc',
		   	$expr->eq($pf . 'circle_id', 'lc.unique_id')
		   );
	}


	/**
	 * link to the groupId/UserId of the NC DB.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 * @param string $field
	 *
	 * @throws GSStatusException
	 */
	protected function leftJoinNCGroupAndUser(IQueryBuilder $qb, $userId, $field) {
		return;
		if (!$this->configService->isLinkedGroupsAllowed()) {
			return;
		}

		$expr = $qb->expr();
		$qb->leftJoin(
			$this->default_select_alias, self::NC_TABLE_GROUP_USER, 'ncgu',
			$expr->eq('ncgu.uid', $qb->createNamedParameter($userId))
		);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->leftJoin(
			$this->default_select_alias, DeprecatedRequestBuilder::TABLE_MEMBERS, 'g',
			$expr->andX(
				$expr->eq('g.user_id', 'ncgu.gid'),
				$expr->eq('g.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_GROUP)),
				$expr->eq('g.instance', $qb->createNamedParameter('')),
				$expr->eq('g.circle_id', $field)
			)
		);

		$this->leftJoinedNCGroupAndUser = true;
	}
}
