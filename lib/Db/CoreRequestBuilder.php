<?php

declare(strict_types=1);


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


use daita\MySmallPhpTools\Db\Nextcloud\nc22\NC22ExtendedQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use OC;
use OCA\Circles\IFederatedModel;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCP\DB\QueryBuilder\ICompositeExpression;

/**
 * Class CoreRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class CoreRequestBuilder extends NC22ExtendedQueryBuilder {


	const PREFIX_MEMBER = 'member_';
	const PREFIX_CIRCLE = 'circle_';
	const PREFIX_OWNER = 'owner_';
	const PREFIX_OWNER_BASED_ON = 'owner_based_on_';
	const PREFIX_INITIATOR = 'initiator_';
	const PREFIX_INITIATOR_BASED_ON = 'initiator_based_on_';
	const PREFIX_INITIATOR_MEMBERSHIP = 'initiator_membership_';
	const PREFIX_INITIATOR_INHERITED_BY = 'initiator_inherited_by_';
	const PREFIX_BASED_ON = 'based_on_';
	const PREFIX_BASED_ON_INITIATOR = 'based_on_initiator_';
	const PREFIX_BASED_ON_INITIATOR_INHERITED_BY = 'based_on_initiator_inherited_by_';
	const PREFIX_BASED_ON_INITIATOR_INHERITED_BY_MEMBERSHIP = 'based_on_initiator_inherited_by_membership_';

	const EXTENSION_CIRCLES = '_circles';
	const EXTENSION_MEMBERS = '_members';
	const EXTENSION_OWNER = '_owner';
	const EXTENSION_BASED_ON = '_based_on';
	const EXTENSION_INITIATOR = '_initiator';
	const EXTENSION_MEMBERSHIPS = '_memberships';
	const EXTENSION_INHERITED_BY = '_inherited_by';


	static $IMPORT_CIRCLE = [
		'',
		self::PREFIX_MEMBER
	];

	static $IMPORT_BASED_ON = [
		'',
		self::PREFIX_MEMBER
	];

	static $IMPORT_OWNER = [
		'',
		self::PREFIX_CIRCLE
	];

	static $IMPORT_INITIATOR_BASED_ON = [
		self::PREFIX_INITIATOR
	];

	static $IMPORT_INITIATOR_INHERITED_BY = [
		self::PREFIX_INITIATOR
	];

	static $IMPORT_OWNER_BASED_ON = [
		self::PREFIX_OWNER
	];

	static $IMPORT_INITIATOR = [
		'',
		self::PREFIX_CIRCLE
	];

	static $IMPORT_BASED_ON_INITIATOR = [
		self::PREFIX_BASED_ON
	];

	static $IMPORT_BASED_ON_INITIATOR_INHERITED_BY = [
		self::PREFIX_BASED_ON_INITIATOR
	];

	static $IMPORT_BASED_ON_INITIATOR_INHERITED_BY_MEMBERSHIP = [
		self::PREFIX_BASED_ON_INITIATOR_INHERITED_BY
	];


	/** @var ConfigService */
	private $configService;


	/**
	 * CoreRequestBuilder constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->configService = OC::$server->get(ConfigService::class);
	}


	/**
	 * @param IFederatedModel $federatedModel
	 *
	 * @return string
	 */
	public function getInstance(IFederatedModel $federatedModel): string {
		$instance = $federatedModel->getInstance();

		return ($this->configService->isLocalInstance($instance)) ? '' : $instance;
	}


	/**
	 * @param string $id
	 */
	public function limitToCircleId(string $id): void {
		$this->limitToDBField('circle_id', $id, true);
	}

	/**
	 * @param string $name
	 */
	public function limitToName(string $name): void {
		$this->limitToDBField('name', $name);
	}

	/**
	 * @param int $config
	 */
	public function limitToConfig(int $config): void {
		$this->limitToDBFieldInt('config', $config);
	}

	/**
	 * @param int $config
	 */
	public function limitToConfigFlag(int $config): void {
		$this->andWhere($this->expr()->bitwiseAnd($this->getDefaultSelectAlias() . '.config', $config));
	}


	/**
	 * @param string $singleId
	 */
	public function limitToSingleId(string $singleId): void {
		$this->limitToDBField('single_id', $singleId, true);
	}


	/**
	 * @param string $itemId
	 */
	public function limitToItemId(string $itemId): void {
		$this->limitToDBField('item_id', $itemId, true);
	}


	/**
	 * @param string $host
	 */
	public function limitToInstance(string $host): void {
		$this->limitToDBField('instance', $host, false);
	}


	/**
	 * @param int $userType
	 */
	public function limitToUserType(int $userType): void {
		$this->limitToDBFieldInt('user_type', $userType);
	}


	/**
	 * @param string $instance
	 * @param bool $sensitive
	 * @param string $aliasCircle
	 * @param string $aliasRemote
	 */
	public function limitToRemoteInstance(
		string $instance,
		bool $sensitive = true,
		string $aliasCircle = 'c',
		string $aliasRemote = 'remote'
	): void {
		$this->leftJoinRemoteInstance($instance, $aliasRemote);
		$this->leftJoinMemberFromInstance($instance, $aliasRemote, $aliasCircle);
		$this->leftJoinMemberFromRemoteCircle($instance, $aliasRemote, $aliasCircle);
		$this->limitRemoteVisibility($sensitive, $aliasRemote, $aliasCircle);
	}


	/**
	 * @param Circle $circle
	 */
	public function filterCircle(Circle $circle): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		if ($circle->getDisplayName() !== '') {
			$this->searchInDBField('display_name', '%' . $circle->getDisplayName() . '%');
		}
	}


	/**
	 * @param Member $member
	 */
	public function limitToMembership(Member $member): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();

		$alias = 'm';
		$this->generateMemberSelectAlias($alias, self::PREFIX_MEMBER)
			 ->leftJoin(
				 $this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $alias,
				 $expr->eq($alias . '.circle_id', $this->getDefaultSelectAlias() . '.unique_id')
			 );

		$this->filterMembership($member, $alias);
	}


	/**
	 * @param Member $member
	 * @param string $alias
	 */
	public function filterMembership(Member $member, string $alias = ''): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$alias = ($alias === '') ? $this->getDefaultSelectAlias() : $alias;
		$expr = $this->expr();
		$andX = $expr->andX();

		if ($member->getUserId() !== '') {
			$andX->add($expr->eq($alias . '.user_id', $this->createNamedParameter($member->getUserId())));
		}

		if ($member->getUserType() > 0) {
			$andX->add($expr->eq($alias . '.user_type', $this->createNamedParameter($member->getUserType())));
		}

		if ($member->getInstance() !== '') {
			$andX->add(
				$expr->eq($alias . '.instance', $this->createNamedParameter($this->getInstance($member)))
			);
		}

		if ($member->getLevel() > 0) {
			$andX->add($expr->gte($alias . '.level', $this->createNamedParameter($member->getLevel())));
		}

		$this->andWhere($andX);
	}


	/**
	 * @param IFederatedUser|null $initiator
	 * @param bool $getData
	 */
	public function leftJoinCircle(
		?IFederatedUser $initiator = null,
		bool $getData = true
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();

		$alias = 'c';
		if ($getData) {
			$this->generateCircleSelectAlias($alias, self::PREFIX_CIRCLE);
		}

		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_CIRCLE, $alias,
			$expr->eq($alias . '.unique_id', $this->getDefaultSelectAlias() . '.circle_id')
		);

		if (!is_null($initiator)) {
			$this->limitToInitiator($initiator, $alias, true, false, $getData);
		}

		$this->leftJoinOwner($alias);
	}


	/**
	 * @param string $aliasMember
	 * @param string $prefixBasedOn
	 * @param IFederatedUser|null $initiator
	 */
	public function leftJoinBasedOnCircle(
		string $aliasMember,
		string $prefixBasedOn = self::PREFIX_BASED_ON,
		?IFederatedUser $initiator = null
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasCircle = $aliasMember . self::EXTENSION_BASED_ON;

		$expr = $this->expr();
		$this->generateCircleSelectAlias($aliasCircle, $prefixBasedOn)
			 ->leftJoin(
				 $aliasMember, CoreQueryBuilder::TABLE_CIRCLE, $aliasCircle,
				 $expr->eq($aliasCircle . '.unique_id', $aliasMember . '.single_id')
			 );

		if (!is_null($initiator)) {
			$this->leftJoinInitiator(
				$initiator,
				$aliasCircle,
				self::PREFIX_BASED_ON_INITIATOR,
				self::PREFIX_BASED_ON_INITIATOR_INHERITED_BY,
				self::PREFIX_BASED_ON_INITIATOR_INHERITED_BY_MEMBERSHIP
			);
		}
	}


	/**
	 * @param string $aliasCircle
	 */
	public function leftJoinOwner(string $aliasCircle = ''): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		if ($aliasCircle === '') {
			$aliasCircle = $this->getDefaultSelectAlias();
		}
		$expr = $this->expr();

		$alias = $aliasCircle . self::EXTENSION_OWNER;
		$this->generateMemberSelectAlias($alias, self::PREFIX_OWNER)
			 ->leftJoin(
				 $aliasCircle, CoreQueryBuilder::TABLE_MEMBER, $alias,
				 $expr->andX(
					 $expr->eq($alias . '.circle_id', $aliasCircle . '.unique_id'),
					 $expr->eq($alias . '.level', $this->createNamedParameter(Member::LEVEL_OWNER))
				 )
			 );

		$this->leftJoinBasedOnCircle($alias, self::PREFIX_OWNER_BASED_ON);
	}


	/**
	 * @param IFederatedUser $initiator
	 * @param string $aliasCircle
	 * @param bool $mustBeMember
	 * @param bool $canBeVisitor
	 * @param bool $getData
	 */
	public function limitToInitiator(
		IFederatedUser $initiator,
		string $aliasCircle = '',
		bool $mustBeMember = false,
		bool $canBeVisitor = false,
		bool $getData = true
	): void {
		$aliasCircle = ($aliasCircle === '') ? $this->getDefaultSelectAlias() : $aliasCircle;

		$this->leftJoinInitiator(
			$initiator,
			$aliasCircle,
			self::PREFIX_INITIATOR,
			self::PREFIX_INITIATOR_INHERITED_BY,
			self::PREFIX_INITIATOR_MEMBERSHIP,
			$getData
		);
		$this->limitInitiatorVisibility($aliasCircle, $mustBeMember, $canBeVisitor);
		if ($getData) {
			$this->leftJoinBasedOnCircle(
				$aliasCircle . self::EXTENSION_INITIATOR,
				self::PREFIX_INITIATOR_BASED_ON
			);
		}
	}


	/**
	 * Left join members to filter userId as initiator.
	 *
	 * @param IFederatedUser $initiator
	 * @param string $aliasCircle
	 * @param string $prefixInitiator
	 * @param string $prefixInitiatorInheritedBy
	 * @param string $prefixMembership
	 * @param bool $getData
	 */
	public function leftJoinInitiator(
		IFederatedUser $initiator,
		string $aliasCircle = 'c',
		string $prefixInitiator = self::PREFIX_INITIATOR,
		string $prefixInitiatorInheritedBy = self::PREFIX_INITIATOR_INHERITED_BY,
		string $prefixMembership = self::PREFIX_INITIATOR_MEMBERSHIP,
		bool $getData = true
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasInitiator = $aliasCircle . self::EXTENSION_INITIATOR;
		$aliasInitiatorMembership = $aliasInitiator . self::EXTENSION_MEMBERSHIPS;
		$aliasInheritedBy = $aliasCircle . self::EXTENSION_INHERITED_BY;

		$expr = $this->expr();

		if ($getData) {
			$this->generateMemberSelectAlias($aliasInitiator, $prefixInitiator);
			$this->generateMemberSelectAlias($aliasInheritedBy, $prefixInitiatorInheritedBy);
			$this->generateMembershipSelectAlias($aliasInitiatorMembership, $prefixMembership);
		}

		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBERSHIP, $aliasInitiatorMembership,
			$expr->andX(
				$expr->eq(
					$aliasInitiatorMembership . '.single_id',
					$this->createNamedParameter($initiator->getSingleId())
				),
				$expr->eq($aliasInitiatorMembership . '.circle_id', $aliasCircle . '.unique_id')
			)
		);

		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasInitiator,
			$expr->andX(
				$expr->eq($aliasInitiatorMembership . '.parent', $aliasInitiator . '.single_id'),
				$expr->eq($aliasInitiatorMembership . '.circle_id', $aliasInitiator . '.circle_id')
			)
		);

		$this->leftJoin(
			$aliasInitiatorMembership, CoreQueryBuilder::TABLE_MEMBER, $aliasInheritedBy,
			$expr->andX(
				$expr->eq($aliasInitiatorMembership . '.single_id', $aliasInheritedBy . '.single_id'),
				$expr->eq($aliasInitiatorMembership . '.single_id', $aliasInheritedBy . '.circle_id')
			)
		);

	}


	/**
	 * left join members to check memberships of someone from instance
	 *
	 * @param string $instance
	 * @param string $aliasRemote
	 * @param string $aliasCircle
	 */
	private function leftJoinMemberFromInstance(string $instance, string $aliasRemote, string $aliasCircle) {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasRemoteMembers = $aliasRemote . self::EXTENSION_MEMBERS;
		$expr = $this->expr();

		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasRemoteMembers,
			$expr->andX(
				$expr->eq($aliasRemoteMembers . '.circle_id', $aliasCircle . '.unique_id'),
				$expr->eq($aliasRemoteMembers . '.instance', $this->createNamedParameter($instance)),
				$expr->gte($aliasRemoteMembers . '.level', $this->createNamedParameter(Member::LEVEL_MEMBER))
			)
		);
	}


	/**
	 * Left join remotes to filter visibility based on RemoteInstance.
	 *
	 * @param string $instance
	 * @param string $aliasRemote
	 */
	public function leftJoinRemoteInstance(string $instance, string $aliasRemote = 'remote'): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_REMOTE, $aliasRemote,
			$expr->eq($aliasRemote . '.instance', $this->createNamedParameter($instance))
		);
	}


	/**
	 * left join circle is member of a circle from remote instance
	 *
	 * @param string $instance
	 * @param string $aliasRemote
	 * @param string $aliasCircle
	 */
	private function leftJoinMemberFromRemoteCircle(string $instance, string $aliasRemote, string $aliasCircle
	) {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasRemoteCircle = $aliasRemote . self::EXTENSION_CIRCLES;
		$aliasRemoteCircleOwner = $aliasRemoteCircle . self::EXTENSION_OWNER;

		$expr = $this->expr();
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasRemoteCircle,
			$expr->andX(
				$expr->eq($aliasRemoteCircle . '.single_id', $aliasCircle . '.unique_id'),
				$expr->emptyString($aliasRemoteCircle . '.instance'),
				$expr->gte($aliasRemoteCircle . '.level', $this->createNamedParameter(Member::LEVEL_MEMBER))
			)
		);
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasRemoteCircleOwner,
			$expr->andX(
				$expr->eq($aliasRemoteCircle . '.circle_id', $aliasRemoteCircleOwner . '.circle_id'),
				$expr->eq($aliasRemoteCircleOwner . '.instance', $this->createNamedParameter($instance)),
				$expr->eq(
					$aliasRemoteCircleOwner . '.level', $this->createNamedParameter(Member::LEVEL_OWNER)
				)
			)
		);
	}


	/**
	 * @param string $aliasCircle
	 * @param bool $mustBeMember
	 * @param bool $canBeVisitor
	 */
	protected function limitInitiatorVisibility(
		string $aliasCircle = '',
		bool $mustBeMember = false,
		bool $canBeVisitor = false
	) {
		$aliasMembership = $aliasCircle . self::EXTENSION_INITIATOR . self::EXTENSION_MEMBERSHIPS;

		$expr = $this->expr();
		$aliasCircle = ($aliasCircle === '') ? $this->getDefaultSelectAlias() : $aliasCircle;

		// Visibility to non-member is
		// - 0 (default), if initiator is member
		// - 2 (Personal), if initiator is owner)
		// - 4 (Visible to everyone)
		$orX = $expr->orX();
		$orX->add(
			$expr->andX(
				$expr->gte($aliasMembership . '.level', $this->createNamedParameter(Member::LEVEL_MEMBER))
			)
		);
		$orX->add(
			$expr->andX(
				$expr->bitwiseAnd($aliasCircle . '.config', Circle::CFG_PERSONAL),
				$expr->eq($aliasMembership . '.level', $this->createNamedParameter(Member::LEVEL_OWNER))
			)
		);
		if (!$mustBeMember) {
			$orX->add($expr->bitwiseAnd($aliasCircle . '.config', Circle::CFG_VISIBLE));
		}
		if ($canBeVisitor) {
			// TODO: should find a better way, also filter on remote initiator on non-federated ?
			$orX->add($expr->gte($aliasCircle . '.config', $this->createNamedParameter(0)));
		}
		$this->andWhere($orX);


//		$orTypes = $this->generateLimit($qb, $circleUniqueId, $userId, $type, $name, $forceAll);
//		if (sizeof($orTypes) === 0) {
//			throw new ConfigNoCircleAvailableException(
//				$this->l10n->t(
//					'You cannot use the Circles Application until your administrator has allowed at least one type of circles'
//				)
//			);
//		}

//		$orXTypes = $this->expr()
//						 ->orX();
//		foreach ($orTypes as $orType) {
//			$orXTypes->add($orType);
//		}
//
//		$qb->andWhere($orXTypes);
	}


	/**
	 * CFG_SINGLE, CFG_HIDDEN and CFG_BACKEND means hidden from listing.
	 *
	 * @param string $alias
	 * @param int $flag
	 */
	public function filterCircles(
		int $flag = Circle::CFG_SINGLE | Circle::CFG_HIDDEN | Circle::CFG_BACKEND,
		string $alias = ''
	): void {
		if ($flag === 0) {
			return;
		}

		$expr = $this->expr();
		$hide = $expr->andX();
		$alias = ($alias === '') ? $this->getDefaultSelectAlias() : $alias;
		foreach (Circle::$DEF_CFG as $cfg => $v) {
			if ($flag & $cfg) {
				$hide->add($this->createFunction('NOT') . $expr->bitwiseAnd($alias . '.config', $cfg));
			}
		}

		$this->andWhere($hide);
	}


	/**
	 * - global_scale: visibility on all Circles
	 * - trusted: visibility on all FEDERATED Circle if owner is local
	 * - external: visibility on all FEDERATED Circle if owner is local and:
	 *    - with if Circle contains at least one member from the remote instance
	 *    - one circle from the remote instance contains the local circle as member, and confirmed (using
	 *      sync locally)
	 * - passive: like external, but the members list will only contains member from the local instance and
	 * from the remote instance.
	 *
	 * @param bool $sensitive
	 * @param string $aliasRemote
	 * @param string $aliasCircle
	 */
	protected function limitRemoteVisibility(bool $sensitive, string $aliasRemote, string $aliasCircle) {
		$aliasOwner = $aliasCircle . self::EXTENSION_OWNER;
		$aliasRemoteMember = $aliasRemote . self::EXTENSION_MEMBERS;
		$aliasRemoteCircle = $aliasRemote . self::EXTENSION_CIRCLES;
		$aliasRemoteCircleOwner = $aliasRemoteCircle . self::EXTENSION_OWNER;

		$expr = $this->expr();
		$orX = $expr->orX();
		$orX->add(
			$expr->eq($aliasRemote . '.type', $this->createNamedParameter(RemoteInstance::TYPE_GLOBAL_SCALE))
		);

		$orExtOrPassive = $expr->orX();
		$orExtOrPassive->add(
			$expr->eq($aliasRemote . '.type', $this->createNamedParameter(RemoteInstance::TYPE_EXTERNAL))
		);
		if (!$sensitive) {
			$orExtOrPassive->add(
				$expr->eq($aliasRemote . '.type', $this->createNamedParameter(RemoteInstance::TYPE_PASSIVE))
			);
		} else {
			if ($this->getDefaultSelectAlias() === 'm') {
				$orExtOrPassive->add($this->limitRemoteVisibility_Sensitive_Members($aliasRemote));
			}
		}


		$orInstance = $expr->orX();
		$orInstance->add($expr->isNotNull($aliasRemoteMember . '.instance'));
		$orInstance->add($expr->isNotNull($aliasRemoteCircleOwner . '.instance'));

		$andExternal = $expr->andX();
		$andExternal->add($orExtOrPassive);
		$andExternal->add($orInstance);

		$orExtOrTrusted = $expr->orX();
		$orExtOrTrusted->add($andExternal);
		$orExtOrTrusted->add(
			$expr->eq($aliasRemote . '.type', $this->createNamedParameter(RemoteInstance::TYPE_TRUSTED))
		);

		$andTrusted = $expr->andX();
		$andTrusted->add($orExtOrTrusted);
		$andTrusted->add($expr->bitwiseAnd($aliasCircle . '.config', Circle::CFG_FEDERATED));
		$andTrusted->add($expr->emptyString($aliasOwner . '.instance'));
		$orX->add($andTrusted);

		$this->andWhere($orX);
	}


	/**
	 * Limit visibility on Sensitive information when search for members.
	 *
	 * @param string $alias
	 *
	 * @return ICompositeExpression
	 */
	private function limitRemoteVisibility_Sensitive_Members(string $alias = 'ri'): ICompositeExpression {
		$expr = $this->expr();
		$andPassive = $expr->andX();
		$andPassive->add(
			$expr->eq($alias . '.type', $this->createNamedParameter(RemoteInstance::TYPE_PASSIVE))
		);

		$orMemberOrLevel = $expr->orX();
		$orMemberOrLevel->add(
			$expr->eq($this->getDefaultSelectAlias() . '.instance', $alias . '.instance')
		);
		// TODO: do we need this ? (display members from the local instance)
		$orMemberOrLevel->add(
			$expr->emptyString($this->getDefaultSelectAlias() . '.instance')
		);

		$orMemberOrLevel->add(
			$expr->eq(
				$this->getDefaultSelectAlias() . '.level',
				$this->createNamedParameter(Member::LEVEL_OWNER)
			)
		);
		$andPassive->add($orMemberOrLevel);

		return $andPassive;
	}


	/**ha
	 *
	 * @param int $flag
	 */
	public function filterConfig(int $flag): void {
		$this->andWhere($this->expr()->bitwiseAnd($this->getDefaultSelectAlias() . '.config', $flag));
	}


	/**
	 * @param string $alias
	 * @param string $prefix
	 * @param array $default
	 *
	 * @return CoreRequestBuilder
	 */
	private function generateCircleSelectAlias(string $alias, string $prefix, array $default = []): self {
		$fields = [
			'unique_id', 'name', 'display_name', 'source', 'description', 'settings', 'config',
			'contact_addressbook', 'contact_groupname', 'creation'
		];

		$this->generateSelectAlias($fields, $alias, $prefix, $default);

		return $this;
	}

	/**
	 * @param string $alias
	 * @param string $prefix
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateMemberSelectAlias(string $alias, string $prefix, array $default = []): self {
		$fields = [
			'circle_id', 'single_id', 'user_id', 'user_type', 'member_id', 'instance', 'cached_name',
			'cached_update', 'status', 'level', 'note', 'contact_id', 'contact_meta', 'joined'
		];

		$this->generateSelectAlias($fields, $alias, $prefix, $default);

		return $this;
	}


	/**
	 * @param string $alias
	 * @param string $prefix
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateMembershipSelectAlias(string $alias, string $prefix, array $default = []): self {
		$fields = ['single_id', 'circle_id', 'level', 'parent', 'path'];
		$this->generateSelectAlias($fields, $alias, $prefix, $default);

		return $this;
	}

}

