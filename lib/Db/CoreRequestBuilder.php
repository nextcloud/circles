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
use OCA\Circles\Model\FederatedUser;
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


	const SINGLE = 'single';
	const CIRCLE = 'circle';
	const MEMBER = 'member';
	const OWNER = 'owner';
	const BASED_ON = 'basedOn';
	const INITIATOR = 'initiator';
	const MEMBERSHIPS = 'memberships';
	const INHERITED_BY = 'inheritedBy';
	const SHARE = 'share';
	const FILE_CACHE = 'fileCache';
	const STORAGES = 'storages';
	const OPTIONS = 'options';


	public static $SQL_PATH = [
		self::SINGLE => [
			self::MEMBER
		],
		self::CIRCLE => [
			self::OPTIONS   => [
				'getPersonalCircle' => true
			],
			self::MEMBER,
			self::OWNER     => [
				self::BASED_ON
			],
			self::MEMBERSHIPS,
			self::INITIATOR => [
				self::BASED_ON,
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			]
		],
		self::MEMBER => [
			self::MEMBERSHIPS,
			self::CIRCLE   => [
				self::OPTIONS   => [
					'getData' => true
				],
				self::OWNER,
				self::MEMBERSHIPS,
				self::INITIATOR => [
					self::OPTIONS      => [
						'mustBeMember' => true,
						'canBeVisitor' => false
					],
					self::BASED_ON,
					self::INHERITED_BY => [
						self::MEMBERSHIPS
					]
				]
			],
			self::BASED_ON => [
				self::OWNER,
				self::MEMBERSHIPS,
				self::INITIATOR => [
					self::BASED_ON,
					self::INHERITED_BY => [
						self::MEMBERSHIPS
					]
				]
			]
		],
		self::SHARE  => [
			self::SHARE,
			self::FILE_CACHE => [
				self::STORAGES
			],
			self::MEMBERSHIPS,
			self::CIRCLE     => [
				self::OWNER
			],
			self::INITIATOR  => [
				self::BASED_ON,
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			]
		]
	];


	const EXTENSION_CIRCLES = '_circles';
	const EXTENSION_MEMBERS = '_members';
	const EXTENSION_OWNER = '_owner';
	const EXTENSION_BASED_ON = '_based_on';
	const EXTENSION_INITIATOR = '_initiator';
	const EXTENSION_MEMBERSHIPS = '_memberships';
	const EXTENSION_INHERITED_BY = '_inherited_by';


	/** @var ConfigService */
	private $configService;


	/** @var array */
	private $options = [];

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
	 * @param int $shareType
	 */
	public function limitToShareType(int $shareType): void {
		$this->limitToDBFieldInt('share_type', $shareType);
	}


	/**
	 * @param string $shareWith
	 */
	public function limitToShareWith(string $shareWith): void {
		$this->limitToDBField('share_with', $shareWith);
	}


	/**
	 * @param int $nodeId
	 */
	public function limitToFileSource(int $nodeId): void {
		$this->limitToDBFieldInt('file_source', $nodeId);
	}

	/**
	 * @param array $files
	 */
	public function limitToFileSourceArray(array $files): void {
		$this->limitToDBFieldInArray('file_source', $files);
	}


	/**
	 * @param int $shareId
	 */
	public function limitToShareParent(int $shareId): void {
		$this->limitToDBFieldInt('parent', $shareId);
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
	 * @param string $alias
	 * @param Member $member
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToDirectMembership(string $alias, Member $member): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasMember = $this->generateAlias($alias, self::MEMBER, $options);
		$getData = $this->getBool('getData', $options, false);

		$expr = $this->expr();
		if ($getData) {
			$this->generateMemberSelectAlias($aliasMember);
		}
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreQueryBuilder::TABLE_MEMBER, $aliasMember,
			$expr->eq($aliasMember . '.circle_id', $alias . '.unique_id')
		);

		$this->filterDirectMembership($aliasMember, $member);
	}


	/**
	 * @param string $aliasMember
	 * @param Member $member
	 */
	public function filterDirectMembership(string $aliasMember, Member $member): void {
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

		if ($member->getSingleId() !== '') {
			$andX->add(
				$expr->eq($aliasMember . '.single_id', $this->createNamedParameter($member->getSingleId()))
			);
		}

		if ($member->getUserType() > 0) {
			$andX->add(
				$expr->eq($aliasMember . '.user_type', $this->createNamedParameter($member->getUserType()))
			);
		}

		$andX->add(
			$expr->eq($aliasMember . '.instance', $this->createNamedParameter($this->getInstance($member)))
		);

		if ($member->getLevel() > 0) {
			$andX->add($expr->gte($aliasMember . '.level', $this->createNamedParameter($member->getLevel())));
		}

		$this->andWhere($andX);
	}


	/**
	 * @param string $alias
	 * @param IFederatedUser|null $initiator
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinCircle(
		string $alias,
		?IFederatedUser $initiator = null,
		string $field = 'circle_id'
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasCircle = $this->generateAlias($alias, self::CIRCLE, $options);
		$getData = $this->getBool('getData', $options, false);
		$expr = $this->expr();

		if ($getData) {
			$this->generateCircleSelectAlias($aliasCircle);
		}

		$this->leftJoin(
			$alias, CoreQueryBuilder::TABLE_CIRCLE, $aliasCircle,
			$expr->eq($aliasCircle . '.unique_id', $alias . '.' . $field)
		);

		if (!is_null($initiator)) {
//			$this->setOptions(
//				explode('_', $aliasCircle), [
//											  'mustBeMember' => true,
//											  'canBeVisitor' => false
//										  ]
//			);

			$this->limitToInitiator($aliasCircle, $initiator);
		}

		$this->leftJoinOwner($aliasCircle);
	}


	/**
	 * @param string $aliasMember
	 * @param IFederatedUser|null $initiator
	 *
	 * @throws RequestBuilderException
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
			$this->leftJoinInitiator($aliasBasedOn, $initiator);
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
			$aliasOwner = $this->generateAlias($aliasCircle, self::OWNER, $options);
			$getData = $this->getBool('getData', $options, false);
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
	 * @param string $alias
	 * @param string $singleId
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToMemberships(string $alias, string $singleId): void {
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$expr = $this->expr();
		$this->leftJoinMemberships($alias);
		$this->andWhere($expr->eq($aliasMembership . '.circle_id', $this->createNamedParameter($singleId)));
	}


	/**
	 * @param string $alias
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinMemberships(string $alias): void {
		$expr = $this->expr();

		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$this->leftJoin(
			$alias, CoreQueryBuilder::TABLE_MEMBERSHIP, $aliasMembership,
			$expr->andX(
				$expr->eq($aliasMembership . '.single_id', $alias . '.single_id'),
				$expr->eq($aliasMembership . '.parent', $alias . '.circle_id')
			)
		);
	}


	/**
	 * @param string $alias
	 * @param IFederatedUser $user
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToInitiator(string $alias, IFederatedUser $user, string $field = ''): void {
		$this->leftJoinInitiator($alias, $user, $field);
		$this->limitInitiatorVisibility($alias);

		$aliasInitiator = $this->generateAlias($alias, self::INITIATOR, $options);
		$getData = $this->getBool('getData', $options, false);

		if ($getData) {
			$this->leftJoinBasedOn($aliasInitiator);
		}
	}


	/**
	 * Left join members to filter userId as initiator.
	 *
	 * @param string $alias
	 * @param IFederatedUser $initiator
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinInitiator(string $alias, IFederatedUser $initiator, string $field = ''): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$field = ($field === '') ? 'unique_id' : $field;
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$getData = $this->getBool('getData', $options, false);
		$expr = $this->expr();

		$this->leftJoin(
			$alias, CoreQueryBuilder::TABLE_MEMBERSHIP, $aliasMembership,
			$expr->andX(
				$expr->eq(
					$aliasMembership . '.single_id',
					$this->createNamedParameter($initiator->getSingleId())
				),
				$expr->eq($aliasMembership . '.circle_id', $alias . '.' . $field)
			)
		);

		if (!$getData) {
			return;
		}

		try {
			$aliasInitiator = $this->generateAlias($alias, self::INITIATOR);
			$this->leftJoin(
				$aliasMembership, CoreQueryBuilder::TABLE_MEMBER, $aliasInitiator,
				$expr->andX(
					$expr->eq($aliasMembership . '.single_id', $aliasInitiator . '.single_id'),
					$expr->eq($aliasMembership . '.circle_id', $aliasInitiator . '.circle_id')
				)
			);

			$aliasInheritedBy = $this->generateAlias($aliasInitiator, self::INHERITED_BY);
			$this->leftJoin(
				$aliasInitiator, CoreQueryBuilder::TABLE_MEMBER, $aliasInheritedBy,
				$expr->andX(
					$expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.single_id'),
					$expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.circle_id')
				)
			);

			$default = [];
			if ($this->getBool('canBeVisitor', $options)) {
				$default = [
					'user_id'   => $initiator->getUserId(),
					'single_id' => $initiator->getSingleId(),
					'instance'  => $initiator->getInstance(),
					'user_type' => $initiator->getUserType()
				];
			}
			$this->generateMemberSelectAlias($aliasInitiator, $default);

			$this->generateMemberSelectAlias($aliasInheritedBy);
			$aliasInheritedByMembership = $this->generateAlias($aliasInheritedBy, self::MEMBERSHIPS);
			$this->generateMembershipSelectAlias($aliasMembership, $aliasInheritedByMembership);
		} catch (RequestBuilderException $e) {
		}
	}


	/**
	 * @param string $alias
	 *
	 * @throws RequestBuilderException
	 */
	protected function limitInitiatorVisibility(string $alias) {
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);
		$getPersonalCircle = $this->getBool('getPersonalCircle', $options, false);
		$mustBeMember = $this->getBool('mustBeMember', $options, true);
		$canBeVisitor = $this->getBool('canBeVisitor', $options, false);

		$expr = $this->expr();

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

		if ($getPersonalCircle) {
			$orX->add(
				$expr->andX(
					$expr->bitwiseAnd($alias . '.config', Circle::CFG_PERSONAL),
					$expr->eq($aliasMembership . '.level', $this->createNamedParameter(Member::LEVEL_OWNER))
				)
			);
		}
		if (!$mustBeMember) {
			$orX->add($expr->bitwiseAnd($alias . '.config', Circle::CFG_VISIBLE));
		}
		if ($canBeVisitor) {
			// TODO: should find a better way, also filter on remote initiator on non-federated ?
			$orX->add($expr->gte($alias . '.config', $this->createNamedParameter(0)));
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


	/**
	 *
	 * @param string $aliasCircle
	 * @param int $flag
	 */
	public function filterConfig(string $aliasCircle, int $flag): void {
		$this->andWhere($this->expr()->bitwiseAnd($aliasCircle . '.config', $flag));
	}


	/**
	 * Link to storage/filecache
	 *
	 * @param string $aliasShare
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinFileCache(string $aliasShare) {
		$expr = $this->expr();

		$aliasFileCache = $this->generateAlias($aliasShare, self::FILE_CACHE);
		$aliasStorages = $this->generateAlias($aliasFileCache, self::STORAGES);

		$fieldsFileCache = [
			'fileid', 'path', 'permissions', 'storage', 'path_hash', 'parent', 'name', 'mimetype', 'mimepart',
			'size', 'mtime', 'storage_mtime', 'encrypted', 'unencrypted_size', 'etag', 'checksum'
		];

		$this->generateSelectAlias($fieldsFileCache, $aliasFileCache, $aliasFileCache, [])
			 ->generateSelectAlias(['id'], $aliasStorages, $aliasStorages, [])
			 ->leftJoin(
				 $aliasShare, CoreQueryBuilder::TABLE_FILE_CACHE, $aliasFileCache,
				 $expr->eq($aliasShare . '.file_source', $aliasFileCache . '.fileid')
			 )
			 ->leftJoin(
				 $aliasFileCache, CoreQueryBuilder::TABLE_STORAGES, $aliasStorages,
				 $expr->eq($aliasFileCache . '.storage', $aliasStorages . '.numeric_id')
			 );
	}


	/**
	 * @param string $aliasShare
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinShareChild(string $aliasShare) {
		$expr = $this->expr();

		$aliasShareChild = $this->generateAlias($aliasShare, self::SHARE);
		$aliasShareMembers = $this->generateAlias($aliasShare, self::MEMBERSHIPS);

		$this->leftJoin(
			$aliasShare, CoreQueryBuilder::TABLE_SHARE, $aliasShareChild,
			$expr->andX(
				$expr->eq($aliasShareChild . '.parent', $aliasShare . '.id'),
				$expr->eq($aliasShareChild . '.share_with', $aliasShareMembers . '.single_id')
			)
		);

		$this->selectAlias($aliasShareChild . '.id', 'child_id');
		$this->selectAlias($aliasShareChild . '.file_target', 'child_file_target');
//		$this->selectAlias($aliasShareParent . '.permissions', 'parent_perms');
	}


	/**
	 * @param string $alias
	 * @param FederatedUser $federatedUser
	 * @param bool $reshares
	 */
	public function limitToShareOwner(string $alias, FederatedUser $federatedUser, bool $reshares): void {
		$expr = $this->expr();

		$orX = $expr->orX(
			$expr->eq($alias . '.uid_initiator', $this->createNamedParameter($federatedUser->getUserId()))
		);

		if ($reshares) {
			$orX->add(
				$expr->eq($alias . '.uid_owner', $this->createNamedParameter($federatedUser->getUserId()))
			);
		}

		$this->andWhere($orX);
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
	 * @param string $prefix
	 *
	 * @return $this
	 */
	private function generateMembershipSelectAlias(
		string $alias,
		string $prefix = '',
		array $default = []
	): self {
		$fields = ['single_id', 'circle_id', 'level', 'parent', 'path'];
		$this->generateSelectAlias($fields, $alias, ($prefix === '') ? $alias : $prefix, $default);

		return $this;
	}


	/**
	 * @param array $path
	 * @param array $options
	 */
	public function setOptions(array $path, array $options): void {
		$options = [self::OPTIONS => $options];
		foreach (array_reverse($path) as $item) {
			$options = [$item => $options];
		}

		$this->options = $options;
	}


	/**
	 * @param string $base
	 * @param string $extension
	 * @param array|null $options
	 *
	 * @return string
	 * @throws RequestBuilderException
	 */
	public function generateAlias(string $base, string $extension, ?array &$options = []): string {
		$search = str_replace('_', '.', $base);
		$path = $search . '.' . $extension;
		if (!$this->validKey($path, self::$SQL_PATH)
			&& !in_array($extension, $this->getArray($search, self::$SQL_PATH))) {
			throw new RequestBuilderException($extension . ' not found in ' . $search);
		}

		if (!is_array($options)) {
			$options = [];
		}

		$optionPath = '';
		foreach (explode('.', $path) as $p) {
			$optionPath = trim($optionPath . '.' . $p, '.');
			$options = array_merge(
				$options,
				$this->getArray($optionPath . '.' . self::OPTIONS, self::$SQL_PATH),
				$this->getArray($optionPath . '.' . self::OPTIONS, $this->options)
			);
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

