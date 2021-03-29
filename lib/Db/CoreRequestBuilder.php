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
use daita\MySmallPhpTools\Traits\TArrayTools;
use Doctrine\DBAL\Query\QueryBuilder;
use OC;
use OCA\Circles\Exceptions\RequestBuilderException;
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


	use TArrayTools;


	const CIRCLE = 'circle';
	const MEMBER = 'member';
	const OWNER = 'owner';
	const BASED_ON = 'basedOn';
	const INITIATOR = 'initiator';
	const MEMBERSHIPS = 'memberships';
	const INHERITED_BY = 'inheritedBy';

	public static $SQL_PATH = [
		self::CIRCLE => [
			self::MEMBER,
			self::OWNER     => [
				self::BASED_ON
			],
			self::INITIATOR => [
				self::BASED_ON,
				self::MEMBERSHIPS,
				self::INHERITED_BY
			]
		],
		self::MEMBER => [
			self::CIRCLE   => [
				self::INITIATOR => [
					self::BASED_ON,
					self::MEMBERSHIPS,
					self::INHERITED_BY
				]
			],
			self::BASED_ON => [
				self::OWNER,
				self::INITIATOR    => [
					self::BASED_ON,
					self::MEMBERSHIPS,
					self::INHERITED_BY
				],
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			]
		],
	];


	// deprecated
	const PREFIX_MEMBER = 'member_';
	const PREFIX_CIRCLE = 'circle_';
	const PREFIX_OWNER = 'owner_';
	const PREFIX_BASED_ON = 'based_on_';
	const PREFIX_INITIATOR = 'initiator_';
	const PREFIX_MEMBERSHIPS = 'memberships_';
	const PREFIX_INHERITED_BY = 'inherited_by_';
	const PREFIX_OWNER_BASED_ON = 'owner_based_on_';
	const PREFIX_INITIATOR_BASED_ON = 'initiator_based_on_';
	const PREFIX_INITIATOR_MEMBERSHIP = 'initiator_membership_';
	const PREFIX_INITIATOR_INHERITED_BY = 'initiator_inherited_by_';
	const PREFIX_BASED_ON_OWNER = 'based_on_owner_';
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
	 * @param string $aliasCircle
	 * @param Member $member
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToMembership(string $aliasCircle, Member $member): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasMember = $this->generateAlias($aliasCircle, self::MEMBER);

		$expr = $this->expr();
		$this->generateMemberSelectAlias($aliasMember)
			 ->leftJoin(
				 $this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasMember,
				 $expr->eq($aliasMember . '.circle_id', $this->getDefaultSelectAlias() . '.unique_id')
			 );

		$this->filterMembership($aliasMember, $member);
	}


	/**
	 * @param string $aliasMember
	 * @param Member $member
	 */
	public function filterMembership(string $aliasMember, Member $member): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();
		$andX = $expr->andX();

		if ($member->getUserId() !== '') {
			$andX->add(
				$expr->eq($aliasMember . '.user_id', $this->createNamedParameter($member->getUserId()))
			);
		}

		if ($member->getUserType() > 0) {
			$andX->add(
				$expr->eq($aliasMember . '.user_type', $this->createNamedParameter($member->getUserType()))
			);
		}

		if ($member->getInstance() !== '') {
			$andX->add(
				$expr->eq(
					$aliasMember . '.instance', $this->createNamedParameter($this->getInstance($member))
				)
			);
		}

		if ($member->getLevel() > 0) {
			$andX->add($expr->gte($aliasMember . '.level', $this->createNamedParameter($member->getLevel())));
		}

		$this->andWhere($andX);
	}


	/**
	 * @param string $aliasMember
	 * @param IFederatedUser|null $initiator
	 * @param bool $getData
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinCircle(
		string $aliasMember,
		?IFederatedUser $initiator = null,
		bool $getData = true
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasCircle = $this->generateAlias($aliasMember, self::CIRCLE);
		$expr = $this->expr();

		if ($getData) {
			$this->generateCircleSelectAlias($aliasCircle);
		}

		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_CIRCLE, $aliasCircle,
			$expr->eq($aliasCircle . '.unique_id', $this->getDefaultSelectAlias() . '.circle_id')
		);

		if (!is_null($initiator)) {
			$this->limitToInitiator($aliasCircle, $initiator, true, false, $getData);
		}

		$this->leftJoinOwner($aliasCircle);
	}


	/**
	 * @param string $aliasMember
	 * @param IFederatedUser|null $initiator
	 */
	public function leftJoinBasedOn(
		string $aliasMember,
		?IFederatedUser $initiator = null
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasBasedOn = $this->generateAlias($aliasMember, self::BASED_ON);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateCircleSelectAlias($aliasBasedOn)
			 ->leftJoin(
				 $aliasMember, CoreQueryBuilder::TABLE_CIRCLE, $aliasBasedOn,
				 $expr->eq($aliasBasedOn . '.unique_id', $aliasMember . '.single_id')
			 );

		if (!is_null($initiator)) {
			try {
				$this->leftJoinInitiator($aliasBasedOn, $initiator);
			} catch (RequestBuilderException $e) {
			}
			$this->leftJoinOwner($aliasBasedOn);
		}
	}


	/**
	 * @param string $aliasCircle
	 */
	public function leftJoinOwner(string $aliasCircle): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasOwner = $this->generateAlias($aliasCircle, self::OWNER);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateMemberSelectAlias($aliasOwner)
			 ->leftJoin(
				 $aliasCircle, CoreQueryBuilder::TABLE_MEMBER, $aliasOwner,
				 $expr->andX(
					 $expr->eq($aliasOwner . '.circle_id', $aliasCircle . '.unique_id'),
					 $expr->eq($aliasOwner . '.level', $this->createNamedParameter(Member::LEVEL_OWNER))
				 )
			 );

		$this->leftJoinBasedOn($aliasOwner);
	}


	/**
	 * @param IFederatedUser $initiator
	 * @param string $aliasCircle
	 * @param bool $mustBeMember
	 * @param bool $canBeVisitor
	 * @param bool $getData
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToInitiator(
		string $aliasCircle,
		IFederatedUser $initiator,
		bool $mustBeMember = false,
		bool $canBeVisitor = false,
		bool $getData = true
	): void {
		$this->leftJoinInitiator(
			$aliasCircle,
			$initiator,
			$getData
		);
		$this->limitInitiatorVisibility($aliasCircle, $mustBeMember, $canBeVisitor);

		$aliasInitiator = $this->generateAlias($aliasCircle, self::INITIATOR);
		if ($getData) {
			$this->leftJoinBasedOn($aliasInitiator);
		}
	}


	/**
	 * Left join members to filter userId as initiator.
	 *
	 * @param IFederatedUser $initiator
	 * @param string $aliasCircle
	 * @param bool $getData
	 */
	public function leftJoinInitiator(
		string $aliasCircle,
		IFederatedUser $initiator,
		bool $getData = true
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasInitiator = $this->generateAlias($aliasCircle, self::INITIATOR);
			$aliasInheritedBy = $this->generateAlias($aliasInitiator, self::INHERITED_BY);
			$aliasMembership = $this->generateAlias($aliasInitiator, self::MEMBERSHIPS);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();

		if ($getData) {
			$this->generateMemberSelectAlias($aliasInitiator);
			$this->generateMemberSelectAlias($aliasInheritedBy);
			$this->generateMembershipSelectAlias($aliasMembership);
		}

		$this->leftJoin(
			$aliasCircle, CoreQueryBuilder::TABLE_MEMBERSHIP, $aliasMembership,
			$expr->andX(
				$expr->eq(
					$aliasMembership . '.single_id',
					$this->createNamedParameter($initiator->getSingleId())
				),
				$expr->orX(
					$expr->eq($aliasMembership . '.circle_id', $aliasCircle . '.unique_id'),
					$expr->eq($aliasMembership . '.parent', $aliasCircle . '.unique_id')
				)
			)
		);

		$this->leftJoin(
			$aliasMembership, CoreQueryBuilder::TABLE_MEMBER, $aliasInitiator,
			$expr->andX(
				$expr->eq($aliasMembership . '.parent', $aliasInitiator . '.single_id'),
				$expr->eq($aliasMembership . '.circle_id', $aliasInitiator . '.circle_id')
			)
		);

		$this->leftJoin(
			$aliasMembership, CoreQueryBuilder::TABLE_MEMBER, $aliasInheritedBy,
			$expr->andX(
				$expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.single_id'),
				$expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.circle_id')
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
	 *
	 * @throws RequestBuilderException
	 */
	protected function limitInitiatorVisibility(
		string $aliasCircle = '',
		bool $mustBeMember = false,
		bool $canBeVisitor = false
	) {

		$aliasInitiator = $this->generateAlias($aliasCircle, self::INITIATOR);
		$aliasMembership = $this->generateAlias($aliasInitiator, self::MEMBERSHIPS);

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
	 * @param string $aliasCircle
	 * @param int $flag
	 */
	public function filterCircles(
		string $aliasCircle,
		int $flag = Circle::CFG_SINGLE | Circle::CFG_HIDDEN | Circle::CFG_BACKEND
	): void {
		if ($flag === 0) {
			return;
		}

		$expr = $this->expr();
		$hide = $expr->andX();
		foreach (Circle::$DEF_CFG as $cfg => $v) {
			if ($flag & $cfg) {
				$hide->add($this->createFunction('NOT') . $expr->bitwiseAnd($aliasCircle . '.config', $cfg));
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
	 * @param string $aliasCircle
	 * @param int $flag
	 */
	public function filterConfig(string $aliasCircle, int $flag): void {
		$this->andWhere($this->expr()->bitwiseAnd($aliasCircle . '.config', $flag));
	}


	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return CoreRequestBuilder
	 */
	private function generateCircleSelectAlias(string $alias, array $default = []): self {
		$fields = [
			'unique_id', 'name', 'display_name', 'source', 'description', 'settings', 'config',
			'contact_addressbook', 'contact_groupname', 'creation'
		];

		$this->generateSelectAlias($fields, $alias, $alias, $default);

		return $this;
	}

	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateMemberSelectAlias(string $alias, array $default = []): self {
		$fields = [
			'circle_id', 'single_id', 'user_id', 'user_type', 'member_id', 'instance', 'cached_name',
			'cached_update', 'status', 'level', 'note', 'contact_id', 'contact_meta', 'joined'
		];

		$this->generateSelectAlias($fields, $alias, $alias, $default);

		return $this;
	}


	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateMembershipSelectAlias(string $alias, array $default = []): self {
		$fields = ['single_id', 'circle_id', 'level', 'parent', 'path'];
		$this->generateSelectAlias($fields, $alias, $alias, $default);

		return $this;
	}


	/**
	 * @param array $path
	 * @param array $options
	 */
	public function setOptions(array $path, array $options) {

	}

	/**
	 * @param string $base
	 * @param string $extension
	 *
	 * @return string
	 * @throws RequestBuilderException
	 */
	public function generateAlias(string $base, string $extension): string {
		$search = str_replace('_', '.', $base);
		if (!$this->validKey($search . '.' . $extension, self::$SQL_PATH)
			&& !in_array($extension, $this->getArray($search, self::$SQL_PATH))) {
			throw new RequestBuilderException($extension . ' not found in ' . $search);
		}

		return $base . '_' . $extension;
	}


	/**
	 * @param string $prefix
	 *
	 * @return array
	 */
	public function getAvailablePath(string $prefix): array {
		$prefix = trim($prefix, '_');
		$search = str_replace('_', '.', $prefix);

		$path = [];
		foreach ($this->getArray($search, self::$SQL_PATH) as $arr => $item) {
			if (is_numeric($arr)) {
				$k = $item;
			} else {
				$k = $arr;
			}
			$path[$k] = $prefix . '_' . $k . '_';
		}

		return $path;
	}

}

