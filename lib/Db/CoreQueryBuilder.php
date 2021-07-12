<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use ArtificialOwl\MySmallPhpTools\Db\Nextcloud\nc22\NC22ExtendedQueryBuilder;
use ArtificialOwl\MySmallPhpTools\Traits\TArrayTools;
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
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CoreQueryBuilder
 *
 * @package OCA\Circles\Db
 */
class CoreQueryBuilder extends NC22ExtendedQueryBuilder {
	use TArrayTools;


	public const SINGLE = 'single';
	public const CIRCLE = 'circle';
	public const MEMBER = 'member';
	public const MEMBER_COUNT = 'membercount';
	public const OWNER = 'owner';
	public const FEDERATED_EVENT = 'federatedevent';
	public const REMOTE = 'remote';
	public const BASED_ON = 'basedon';
	public const INITIATOR = 'initiator';
	public const DIRECT_INITIATOR = 'initiatordirect';
	public const MEMBERSHIPS = 'memberships';
	public const CONFIG = 'config';
	public const UPSTREAM_MEMBERSHIPS = 'upstreammemberships';
	public const INHERITANCE_FROM = 'inheritancefrom';
	public const INHERITED_BY = 'inheritedby';
	public const INVITED_BY = 'invitedby';
	public const MOUNT = 'mount';
	public const MOUNTPOINT = 'mountpoint';
	public const SHARE = 'share';
	public const FILE_CACHE = 'filecache';
	public const STORAGES = 'storages';
	public const TOKEN = 'token';
	public const OPTIONS = 'options';
	public const HELPER = 'circleshelper';


	public static $SQL_PATH = [
		self::SINGLE => [
			self::MEMBER
		],
		self::CIRCLE => [
			self::OPTIONS => [
			],
			self::MEMBER,
			self::MEMBER_COUNT,
			self::OWNER => [
				self::BASED_ON
			],
			self::MEMBERSHIPS => [
				self::CONFIG
			],
			self::DIRECT_INITIATOR => [
				self::BASED_ON
			],
			self::INITIATOR => [
				self::BASED_ON,
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			],
			self::REMOTE => [
				self::MEMBER,
				self::CIRCLE => [
					self::OWNER
				]
			]
		],
		self::MEMBER => [
			self::MEMBERSHIPS => [
				self::CONFIG
			],
			self::INHERITANCE_FROM,
			self::CIRCLE => [
				self::OPTIONS => [
					'getData' => true
				],
				self::OWNER,
				self::MEMBERSHIPS => [
					self::CONFIG
				],
				self::INITIATOR => [
					self::OPTIONS => [
						'mustBeMember' => true,
						'canBeVisitor' => false
					],
					self::BASED_ON,
					self::INHERITED_BY => [
						self::MEMBERSHIPS
					],
					self::INVITED_BY => [
						self::OWNER,
						self::BASED_ON
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
			],
			self::REMOTE => [
				self::MEMBER,
				self::CIRCLE => [
					self::OWNER
				]
			],
			self::INVITED_BY => [
				self::OWNER,
				self::BASED_ON
			]
		],
		self::MEMBERSHIPS => [
			self::CONFIG
		],
		self::SHARE => [
			self::SHARE,
			self::TOKEN,
			self::FILE_CACHE => [
				self::STORAGES
			],
			self::UPSTREAM_MEMBERSHIPS => [
				self::MEMBERSHIPS,
				self::INHERITED_BY => [
					self::BASED_ON
				],
				self::SHARE,
			],
			self::MEMBERSHIPS => [
				self::CONFIG
			],
			self::INHERITANCE_FROM,
			self::INHERITED_BY => [
				self::BASED_ON
			],
			self::CIRCLE => [
				self::OWNER
			],
			self::INITIATOR => [
				self::BASED_ON,
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			]
		],
		self::REMOTE => [
			self::MEMBER
		],
		self::MOUNT => [
			self::MEMBER => [
				self::REMOTE
			],
			self::INITIATOR => [
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			],
			self::MOUNTPOINT,
			self::MEMBERSHIPS => [
				self::CONFIG
			]
		],
		self::HELPER => [
			self::MEMBERSHIPS => [
				self::CONFIG
			],
			self::INITIATOR => [
				self::INHERITED_BY => [
					self::MEMBERSHIPS
				]
			],
			self::CIRCLE => [
				self::OPTIONS => [
				],
				self::MEMBER,
				self::OWNER => [
					self::BASED_ON
				]
			]
		]
	];


	/** @var ConfigService */
	private $configService;


	/** @var array */
	private $options = [];


	/**
	 * CoreQueryBuilder constructor.
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
		$this->limit('name', $name);
	}

	/**
	 * @param string $name
	 */
	public function limitToDisplayName(string $name): void {
		$this->limit('display_name', $name, '', false);
	}

	/**
	 * @param string $name
	 */
	public function limitToSanitizedName(string $name): void {
		$this->limit('sanitized_name', $name, '', false);
	}

	/**
	 * @param int $config
	 */
	public function limitToConfig(int $config): void {
		$this->limitToDBFieldInt('config', $config);
	}

	/**
	 * @param int $source
	 */
	public function limitToSource(int $source): void {
		$this->limitToDBFieldInt('source', $source);
	}

	/**
	 * @param int $config
	 * @param string $alias
	 */
	public function limitToConfigFlag(int $config, string $alias = ''): void {
		$this->limitBitwise('config', $config, $alias);
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
		if ($circle->getConfig() > 0) {
			$this->limitBitwise('config', $circle->getConfig());
		}
	}


	/**
	 * left join RemoteInstance based on a Member
	 */
	public function leftJoinRemoteInstance(string $alias): void {
		$expr = $this->expr();

		try {
			$aliasRemoteInstance = $this->generateAlias($alias, self::REMOTE);
			$this->generateRemoteInstanceSelectAlias($aliasRemoteInstance)
				 ->leftJoin(
					 $alias, CoreRequestBuilder::TABLE_REMOTE, $aliasRemoteInstance,
					 $expr->eq($alias . '.instance', $aliasRemoteInstance . '.instance')
				 );
		} catch (RequestBuilderException $e) {
		}
	}


	/**
	 * @param string $alias
	 * @param RemoteInstance $remoteInstance
	 * @param bool $filterSensitiveData
	 * @param string $aliasCircle
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToRemoteInstance(
		string $alias,
		RemoteInstance $remoteInstance,
		bool $filterSensitiveData = true,
		string $aliasCircle = ''
	): void {
		if ($aliasCircle === '') {
			$aliasCircle = $alias;
		}

		$this->leftJoinRemoteInstanceIncomingRequest($alias, $remoteInstance);
		$this->leftJoinMemberFromInstance($alias, $remoteInstance, $aliasCircle);
		$this->leftJoinMemberFromRemoteCircle($alias, $remoteInstance, $aliasCircle);
		$this->limitRemoteVisibility($alias, $filterSensitiveData, $aliasCircle);
	}


	/**
	 * Left join RemoteInstance based on an incoming request
	 *
	 * @param string $alias
	 * @param RemoteInstance $remoteInstance
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinRemoteInstanceIncomingRequest(
		string $alias,
		RemoteInstance $remoteInstance
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasRemote = $this->generateAlias($alias, self::REMOTE);
		$expr = $this->expr();
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_REMOTE, $aliasRemote,
			$expr->eq($aliasRemote . '.instance', $this->createNamedParameter($remoteInstance->getInstance()))
		);
	}


	/**
	 * left join members to check memberships of someone from instance
	 *
	 * @param string $alias
	 * @param RemoteInstance $remoteInstance
	 * @param string $aliasCircle
	 *
	 * @throws RequestBuilderException
	 */
	private function leftJoinMemberFromInstance(
		string $alias, RemoteInstance $remoteInstance, string $aliasCircle
	) {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasRemote = $this->generateAlias($alias, self::REMOTE);
		$aliasRemoteMember = $this->generateAlias($aliasRemote, self::MEMBER);

		$expr = $this->expr();
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBER, $aliasRemoteMember,
			$expr->andX(
				$expr->eq($aliasRemoteMember . '.circle_id', $aliasCircle . '.unique_id'),
				$expr->eq(
					$aliasRemoteMember . '.instance',
					$this->createNamedParameter($remoteInstance->getInstance())
				),
				$expr->gte($aliasRemoteMember . '.level', $this->createNamedParameter(Member::LEVEL_MEMBER))
			)
		);
	}


	/**
	 * left join circle is member of a circle from remote instance
	 *
	 * @param string $alias
	 * @param RemoteInstance $remoteInstance
	 * @param string $aliasCircle
	 *
	 * @throws RequestBuilderException
	 */
	private function leftJoinMemberFromRemoteCircle(
		string $alias,
		RemoteInstance $remoteInstance,
		string $aliasCircle
	) {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$aliasRemote = $this->generateAlias($alias, self::REMOTE);
		$aliasRemoteCircle = $this->generateAlias($aliasRemote, self::CIRCLE);
		$aliasRemoteCircleOwner = $this->generateAlias($aliasRemoteCircle, self::OWNER);

		$expr = $this->expr();
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBER, $aliasRemoteCircle,
			$expr->andX(
				$expr->eq($aliasRemoteCircle . '.single_id', $aliasCircle . '.unique_id'),
				$expr->emptyString($aliasRemoteCircle . '.instance'),
				$expr->gte($aliasRemoteCircle . '.level', $this->createNamedParameter(Member::LEVEL_MEMBER))
			)
		);
		$this->leftJoin(
			$this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBER, $aliasRemoteCircleOwner,
			$expr->andX(
				$expr->eq($aliasRemoteCircle . '.circle_id', $aliasRemoteCircleOwner . '.circle_id'),
				$expr->eq(
					$aliasRemoteCircleOwner . '.instance',
					$this->createNamedParameter($remoteInstance->getInstance())
				),
				$expr->eq(
					$aliasRemoteCircleOwner . '.level', $this->createNamedParameter(Member::LEVEL_OWNER)
				)
			)
		);
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
	 * @param string $alias
	 * @param bool $sensitive
	 * @param string $aliasCircle
	 *
	 * @throws RequestBuilderException
	 */
	protected function limitRemoteVisibility(string $alias, bool $sensitive, string $aliasCircle) {
		$aliasRemote = $this->generateAlias($alias, self::REMOTE);
		$aliasOwner = $this->generateAlias($aliasCircle, self::OWNER);
		$aliasRemoteMember = $this->generateAlias($aliasRemote, self::MEMBER);
		$aliasRemoteCircle = $this->generateAlias($aliasRemote, self::CIRCLE);
		$aliasRemoteCircleOwner = $this->generateAlias($aliasRemoteCircle, self::OWNER);

		$expr = $this->expr();
		$orX = $expr->orX();
		$orX->add(
			$expr->eq($aliasRemote . '.type', $this->createNamedParameter(RemoteInstance::TYPE_GLOBALSCALE))
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
			if ($this->getDefaultSelectAlias() === CoreQueryBuilder::MEMBER) {
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
		$andTrusted->add($this->exprLimitBitwise('config', Circle::CFG_FEDERATED, $aliasCircle));
		$andTrusted->add($expr->emptyString($aliasOwner . '.instance'));
		$orX->add($andTrusted);

		$this->andWhere($orX);
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
			$this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBER, $aliasMember,
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
			$andX->add(
				$expr->gte(
					$aliasMember . '.level',
					$this->createNamedParameter($member->getLevel(), IQueryBuilder::PARAM_INT)
				)
			);
		}

		$this->andWhere($andX);
	}


	/**
	 * @param string $alias
	 */
	public function countMembers(string $alias): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasMemberCount = $this->generateAlias($alias, self::MEMBER_COUNT, $options);
		} catch (RequestBuilderException $e) {
			return;
		}

		$getData = $this->getBool('getData', $options, false);
		if (!$getData) {
			return;
		}

		$expr = $this->expr();
		$this->selectAlias(
			$this->createFunction('COUNT(`' . $aliasMemberCount . '`.`member_id`)'),
			(($alias !== $this->getDefaultSelectAlias()) ? $alias . '_' : '') . 'population'
		);
		$this->leftJoin(
			$alias, CoreRequestBuilder::TABLE_MEMBER, $aliasMemberCount,
			$expr->eq($alias . '.unique_id', $aliasMemberCount . '.circle_id')
		);
	}


	/**
	 * @param string $alias
	 * @param IFederatedUser|null $initiator
	 * @param string $field
	 * @param string $helperAlias
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinCircle(
		string $alias,
		?IFederatedUser $initiator = null,
		string $field = 'circle_id',
		string $helperAlias = ''
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$helperAlias = ($helperAlias !== '') ? $helperAlias : $alias;
		$aliasCircle = $this->generateAlias($alias, self::CIRCLE, $options);
		$getData = $this->getBool('getData', $options, false);
		$expr = $this->expr();

		if ($getData) {
			$this->generateCircleSelectAlias($aliasCircle);
		}

		$this->leftJoin(
			$helperAlias,
			CoreRequestBuilder::TABLE_CIRCLE,
			$aliasCircle,
			$expr->eq($aliasCircle . '.unique_id', $helperAlias . '.' . $field)
		);

		if (!is_null($initiator)) {
			$this->limitToInitiator($aliasCircle, $initiator);
		}

		$this->leftJoinOwner($aliasCircle);
	}


	/**
	 * @param string $aliasMember
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinInvitedBy(string $aliasMember): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasInvitedBy = $this->generateAlias($aliasMember, self::INVITED_BY);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateCircleSelectAlias($aliasInvitedBy)
			 ->leftJoin(
				 $aliasMember, CoreRequestBuilder::TABLE_CIRCLE, $aliasInvitedBy,
				 $expr->eq($aliasMember . '.invited_by', $aliasInvitedBy . '.unique_id')
			 );

		$this->leftJoinOwner($aliasInvitedBy);
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
			$aliasBasedOn = $this->generateAlias($aliasMember, self::BASED_ON, $options);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateCircleSelectAlias($aliasBasedOn)
			 ->leftJoin(
				 $aliasMember, CoreRequestBuilder::TABLE_CIRCLE, $aliasBasedOn,
				 $expr->eq($aliasBasedOn . '.unique_id', $aliasMember . '.single_id')
			 );

		if (!is_null($initiator)) {
			$this->leftJoinInitiator($aliasBasedOn, $initiator);
			$this->leftJoinOwner($aliasBasedOn);
		}
	}


	/**
	 * @param string $alias
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinOwner(string $alias, string $field = 'unique_id'): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasMember = $this->generateAlias($alias, self::OWNER, $options);
			$getData = $this->getBool('getData', $options, false);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateMemberSelectAlias($aliasMember)
			 ->leftJoin(
				 $alias, CoreRequestBuilder::TABLE_MEMBER, $aliasMember,
				 $expr->andX(
					 $expr->eq($aliasMember . '.circle_id', $alias . '.' . $field),
					 $expr->eq(
						 $aliasMember . '.level',
						 $this->createNamedParameter(Member::LEVEL_OWNER, self::PARAM_INT)
					 )
				 )
			 );

		$this->leftJoinBasedOn($aliasMember);
	}


	/**
	 * @param string $alias
	 * @param string $fieldCircleId
	 * @param string $fieldSingleId
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinMember(
		string $alias,
		string $fieldCircleId = 'circle_id',
		string $fieldSingleId = 'single_id'
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		try {
			$aliasMember = $this->generateAlias($alias, self::MEMBER, $options);
			$getData = $this->getBool('getData', $options, false);
		} catch (RequestBuilderException $e) {
			return;
		}

		$expr = $this->expr();
		$this->generateMemberSelectAlias($aliasMember)
			 ->leftJoin(
				 $alias, CoreRequestBuilder::TABLE_MEMBER, $aliasMember,
				 $expr->andX(
					 $expr->eq($aliasMember . '.circle_id', $alias . '.' . $fieldCircleId),
					 $expr->eq($aliasMember . '.single_id', $alias . '.' . $fieldSingleId),
					 $expr->gte(
						 $aliasMember . '.level',
						 $this->createNamedParameter(Member::LEVEL_MEMBER, self::PARAM_INT)
					 )
				 )
			 );

		$this->leftJoinRemoteInstance($aliasMember);
		$this->leftJoinBasedOn($aliasMember);
	}


	/**
	 * if 'getData' is true, will returns 'inheritanceBy': the Member at the end of a sub-chain of
	 * memberships (based on $field for Top Circle's singleId)
	 *
	 * @param string $alias
	 * @param string $field
	 * @param string $aliasInheritedBy
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinInheritedMembers(
		string $alias,
		string $field = '',
		string $aliasInheritedBy = ''
	): void {
		$expr = $this->expr();

		$field = ($field === '') ? 'circle_id' : $field;
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$this->leftJoin(
			$alias, CoreRequestBuilder::TABLE_MEMBERSHIP, $aliasMembership,
			$expr->eq($aliasMembership . '.circle_id', $alias . '.' . $field)
		);

//		if (!$this->getBool('getData', $options, false)) {
//			return;
//		}

		if ($aliasInheritedBy === '') {
			$aliasInheritedBy = $this->generateAlias($alias, self::INHERITED_BY);
		}
		$this->generateMemberSelectAlias($aliasInheritedBy)
			 ->leftJoin(
				 $alias, CoreRequestBuilder::TABLE_MEMBER, $aliasInheritedBy,
				 $expr->andX(
					 $expr->eq($aliasMembership . '.inheritance_last', $aliasInheritedBy . '.circle_id'),
					 $expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.single_id')
				 )
			 );

		$this->leftJoinBasedOn($aliasInheritedBy);
	}


	/**
	 * @throws RequestBuilderException
	 */
	public function limitToInheritedMemberships(string $alias, string $singleId, string $field = ''): void {
		$expr = $this->expr();
		$field = ($field === '') ? 'circle_id' : $field;
		$aliasUpstreamMembership = $this->generateAlias($alias, self::UPSTREAM_MEMBERSHIPS, $options);
		$this->leftJoin(
			$alias, CoreRequestBuilder::TABLE_MEMBERSHIP, $aliasUpstreamMembership,
			$expr->eq($aliasUpstreamMembership . '.single_id', $this->createNamedParameter($singleId))
		);

		$orX = $expr->orX(
			$expr->eq($aliasUpstreamMembership . '.circle_id', $alias . '.' . $field),
			$expr->eq($alias . '.' . $field, $this->createNamedParameter($singleId))
		);

		$this->andWhere($orX);
	}


	/**
	 * limit the request to Members and Sub Members of a Circle.
	 *
	 * @param string $alias
	 * @param string $singleId
	 * @param int $level
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToMembersByInheritance(string $alias, string $singleId, int $level = 0): void {
		$this->leftJoinMembersByInheritance($alias);

		$expr = $this->expr();
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS);
		$this->andWhere($expr->eq($aliasMembership . '.circle_id', $this->createNamedParameter($singleId)));
		if ($level > 1) {
			$this->andWhere(
				$expr->gte(
					$aliasMembership . '.level',
					$this->createNamedParameter($level, IQueryBuilder::PARAM_INT)
				)
			);
		}
	}


	/**
	 * if 'getData' is true, will returns 'inheritanceFrom': the Circle-As-Member of the Top Circle
	 * that explain the membership of a Member (based on $field for singleId) to a specific Circle
	 *
	 * // TODO: returns the link/path ?
	 *
	 * @param string $alias
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinMembersByInheritance(string $alias, string $field = ''): void {
		$expr = $this->expr();

		$field = ($field === '') ? 'circle_id' : $field;
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$this->leftJoin(
			$alias, CoreRequestBuilder::TABLE_MEMBERSHIP, $aliasMembership,
			$expr->andX(
				$expr->eq($aliasMembership . '.inheritance_last', $alias . '.' . $field),
				$expr->eq($aliasMembership . '.single_id', $alias . '.single_id')
			)
		);

		if (!$this->getBool('getData', $options, false)) {
			return;
		}

		$aliasInheritanceFrom = $this->generateAlias($alias, self::INHERITANCE_FROM);
		$this->generateMemberSelectAlias($aliasInheritanceFrom)
			 ->leftJoin(
				 $aliasMembership, CoreRequestBuilder::TABLE_MEMBER, $aliasInheritanceFrom,
				 $expr->andX(
					 $expr->eq($aliasMembership . '.circle_id', $aliasInheritanceFrom . '.circle_id'),
					 $expr->eq($aliasMembership . '.inheritance_first', $aliasInheritanceFrom . '.single_id')
				 )
			 );
	}


	/**
	 * @param string $alias
	 * @param string $token
	 *
	 * @throws RequestBuilderException
	 */
	public function limitToShareToken(string $alias, string $token): void {
		$this->leftJoinShareToken($alias);

		$aliasShareToken = $this->generateAlias($alias, self::TOKEN, $options);
		$this->limit('token', $token, $aliasShareToken);
	}

	/**
	 * @param string $alias
	 * @param string $field
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinShareToken(string $alias, string $field = ''): void {
		$expr = $this->expr();

		$field = ($field === '') ? 'id' : $field;
		$aliasShareToken = $this->generateAlias($alias, self::TOKEN, $options);

		$this->leftJoin(
			$alias, CoreRequestBuilder::TABLE_TOKEN, $aliasShareToken,
			$expr->eq($aliasShareToken . '.share_id', $alias . '.' . $field)
		);
	}


	/**
	 * limit the result to the point of view of a FederatedUser
	 *
	 * @param string $alias
	 * @param IFederatedUser $user
	 * @param string $field
	 * @param string $helperAlias
	 *
	 * @return ICompositeExpression
	 * @throws RequestBuilderException
	 */
	public function limitToInitiator(
		string $alias,
		IFederatedUser $user,
		string $field = '',
		string $helperAlias = ''
	): ICompositeExpression {
		$this->leftJoinInitiator($alias, $user, $field, $helperAlias);
		$where = $this->limitInitiatorVisibility($alias);

		$aliasInitiator = $this->generateAlias($alias, self::INITIATOR, $options);
		if ($this->getBool('getData', $options, false)) {
			$this->leftJoinBasedOn($aliasInitiator);
		}

		return $where;
	}


	/**
	 * @param string $alias
	 */
	public function leftJoinCircleConfig(string $alias): void {
		$expr = $this->expr();
		try {
			$aliasConfig = $this->generateAlias($alias, self::CONFIG, $options);
			$this->selectAlias(
				$aliasConfig . '.config',
				(($alias !== $this->getDefaultSelectAlias()) ? $alias . '_' : '') . 'circle_config'
			);
			$this->leftJoin(
				$alias,
				CoreRequestBuilder::TABLE_CIRCLE,
				$aliasConfig,
				$expr->eq($alias . '.circle_id', $aliasConfig . '.unique_id')
			);
		} catch (RequestBuilderException $e) {
		}
	}


	/**
	 * Left join members to filter userId as initiator.
	 *
	 * @param string $alias
	 * @param IFederatedUser $initiator
	 * @param string $field
	 * @param string $helperAlias
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinInitiator(
		string $alias,
		IFederatedUser $initiator,
		string $field = '',
		string $helperAlias = ''
	): void {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();
		$field = ($field === '') ? 'unique_id' : $field;
		$helperAlias = ($helperAlias !== '') ? $helperAlias : $alias;
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);

		$this->leftJoin(
			$helperAlias,
			CoreRequestBuilder::TABLE_MEMBERSHIP,
			$aliasMembership,
			$expr->andX(
				$this->exprLimit('single_id', $initiator->getSingleId(), $aliasMembership),
				$expr->eq($aliasMembership . '.circle_id', $helperAlias . '.' . $field)
			)
		);

		try {
			$aliasMembershipCircle = $this->generateAlias($aliasMembership, self::CONFIG, $options);
			$this->leftJoin(
				$aliasMembership,
				CoreRequestBuilder::TABLE_CIRCLE,
				$aliasMembershipCircle,
				$expr->eq($aliasMembership . '.circle_id', $aliasMembershipCircle . '.unique_id')
			);
		} catch (RequestBuilderException $e) {
		}

		if (!$this->getBool('getData', $options, false)) {
			return;
		}

		// bypass memberships
		if ($this->getBool('initiatorDirectMember', $options, false)) {
			try {
				$aliasDirectInitiator = $this->generateAlias($alias, self::DIRECT_INITIATOR, $options);

				$this->generateMemberSelectAlias($aliasDirectInitiator)
					 ->leftJoin(
						 $helperAlias,
						 CoreRequestBuilder::TABLE_MEMBER,
						 $aliasDirectInitiator,
						 $expr->andX(
							 $this->exprLimit('single_id', $initiator->getSingleId(), $aliasDirectInitiator),
							 $expr->eq($aliasDirectInitiator . '.circle_id', $helperAlias . '.' . $field)
						 )
					 );
			} catch (RequestBuilderException $e) {
			}
		}


		try {
			$aliasInitiator = $this->generateAlias($alias, self::INITIATOR, $options);
			$this->leftJoin(
				$aliasMembership, CoreRequestBuilder::TABLE_MEMBER, $aliasInitiator,
				$expr->andX(
					$expr->eq($aliasMembership . '.inheritance_first', $aliasInitiator . '.single_id'),
					$expr->eq($aliasMembership . '.circle_id', $aliasInitiator . '.circle_id')
				)
			);

			$aliasInheritedBy = $this->generateAlias($aliasInitiator, self::INHERITED_BY);
			$this->leftJoin(
				$aliasInitiator, CoreRequestBuilder::TABLE_MEMBER, $aliasInheritedBy,
				$expr->andX(
					$expr->eq($aliasMembership . '.single_id', $aliasInheritedBy . '.single_id'),
					$expr->eq($aliasMembership . '.inheritance_last', $aliasInheritedBy . '.circle_id')
				)
			);

			$default = [];
			if ($this->getBool('canBeVisitor', $options, false)) {
				$default = [
					'user_id' => $initiator->getUserId(),
					'single_id' => $initiator->getSingleId(),
					'user_type' => $initiator->getUserType(),
					'cached_name' => $initiator->getDisplayName(),
					'instance' => $initiator->getInstance()
				];
			}
			$aliasInheritedByMembership = $this->generateAlias($aliasInheritedBy, self::MEMBERSHIPS);
			$this->generateMemberSelectAlias($aliasInitiator, $default)
				 ->generateMemberSelectAlias($aliasInheritedBy)
				 ->generateMembershipSelectAlias($aliasMembership, $aliasInheritedByMembership);
		} catch (RequestBuilderException $e) {
		}
	}


	/**
	 * @param string $alias
	 *
	 * @return ICompositeExpression
	 * @throws RequestBuilderException
	 */
	protected function limitInitiatorVisibility(string $alias): ICompositeExpression {
		$aliasMembership = $this->generateAlias($alias, self::MEMBERSHIPS, $options);
		$aliasMembershipCircle = $this->generateAlias($aliasMembership, self::CONFIG, $options);
		$levelCheck = [$aliasMembership];

		if ($this->getBool('initiatorDirectMember', $options, false)) {
			array_push($levelCheck, $this->generateAlias($alias, self::DIRECT_INITIATOR, $options));
		}

		$filterPersonalCircle = $this->getBool('filterPersonalCircle', $options, true);

		$expr = $this->expr();

		// Visibility to non-member is
		// - 0 (default), if initiator is member
		// - 2 (Personal), if initiator is owner)
		// - 4 (Visible to everyone)
		$orX = $expr->orX();

		if ($filterPersonalCircle) {
			$orX->add(
				$expr->andX(
					$this->exprLimitBitwise('config', Circle::CFG_PERSONAL, $aliasMembershipCircle),
					$expr->eq($aliasMembership . '.level', $this->createNamedParameter(Member::LEVEL_OWNER))
				)
			);
		}

		$andXMember = $expr->andX();
		$andXMember->add(
			$this->orXCheckLevel($levelCheck, Member::LEVEL_MEMBER),
		);
		if ($filterPersonalCircle) {
			$andXMember->add(
				$this->exprFilterBitwise('config', Circle::CFG_PERSONAL, $aliasMembershipCircle)
			);
		}
		$orX->add($andXMember);

		if (!$this->getBool('mustBeMember', $options, true)) {
			$orX->add($this->exprLimitBitwise('config', Circle::CFG_VISIBLE, $alias));
		}
		if ($this->getBool('canBeVisitor', $options, false)) {
			// TODO: should find a better way, also filter on remote initiator on non-federated ?
			$orX->add($this->exprFilterInt('config', Circle::CFG_PERSONAL, $alias));
		}
		if ($this->getBool('canBeVisitorOnOpen', $options, false)) {
			$andOpen = $expr->andX();
			$andOpen->add($this->exprLimitBitwise('config', Circle::CFG_OPEN, $alias));
			$andOpen->add($this->exprFilterBitwise('config', Circle::CFG_REQUEST, $alias));
			$orX->add($andOpen);
		}

		$this->andWhere($orX);

		return $orX;
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
				$hide->add($this->exprFilterBitwise('config', $cfg, $aliasCircle));
			}
		}

		$this->andWhere($hide);
	}


	/**
	 * Limit visibility on Sensitive information when search for members.
	 *
	 * @param string $alias
	 *
	 * @return ICompositeExpression
	 */
	private function limitRemoteVisibility_Sensitive_Members(string $alias): ICompositeExpression {
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

		$this->generateSelectAlias(
			CoreRequestBuilder::$outsideTables[CoreRequestBuilder::TABLE_FILE_CACHE],
			$aliasFileCache,
			$aliasFileCache,
			[]
		)
			 ->generateSelectAlias(
				 CoreRequestBuilder::$outsideTables[CoreRequestBuilder::TABLE_STORAGES],
				 $aliasStorages,
				 $aliasStorages,
				 []
			 )
			 ->leftJoin(
				 $aliasShare, CoreRequestBuilder::TABLE_FILE_CACHE, $aliasFileCache,
				 $expr->eq($aliasShare . '.file_source', $aliasFileCache . '.fileid')
			 )
			 ->leftJoin(
				 $aliasFileCache, CoreRequestBuilder::TABLE_STORAGES, $aliasStorages,
				 $expr->eq($aliasFileCache . '.storage', $aliasStorages . '.numeric_id')
			 );
	}


	/**
	 * @param string $aliasShare
	 * @param string $aliasShareMemberships
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinShareChild(string $aliasShare, string $aliasShareMemberships = '') {
		$expr = $this->expr();

		$aliasShareChild = $this->generateAlias($aliasShare, self::SHARE);
		if ($aliasShareMemberships === '') {
			$aliasShareMemberships = $this->generateAlias($aliasShare, self::MEMBERSHIPS, $options);
		}

		$this->leftJoin(
			$aliasShareMemberships, CoreRequestBuilder::TABLE_SHARE, $aliasShareChild,
			$expr->andX(
				$expr->eq($aliasShareChild . '.parent', $aliasShare . '.id'),
				$expr->eq($aliasShareChild . '.share_with', $aliasShareMemberships . '.single_id')
			)
		);

		$this->generateSelectAlias(
			['id', 'file_target', 'permissions'],
			$aliasShareChild,
			'child',
			[]
		);

//		$this->selectAlias($aliasShareParent . '.permissions', 'parent_perms');
	}


	/**
	 * @param string $alias
	 * @param FederatedUser $federatedUser
	 * @param bool $reshares
	 */
	public function limitToShareOwner(string $alias, FederatedUser $federatedUser, bool $reshares): void {
		$expr = $this->expr();

		$orX = $expr->orX($this->exprLimit('uid_initiator', $federatedUser->getUserId(), $alias));

		if ($reshares) {
			$orX->add($this->exprLimit('uid_owner', $federatedUser->getUserId(), $alias));
		}

		$this->andWhere($orX);
	}


	/**
	 * @param string $aliasMount
	 * @param string $aliasMountMemberships
	 *
	 * @throws RequestBuilderException
	 */
	public function leftJoinMountpoint(string $aliasMount, string $aliasMountMemberships = '') {
		$expr = $this->expr();

		$aliasMountpoint = $this->generateAlias($aliasMount, self::MOUNTPOINT);
		if ($aliasMountMemberships === '') {
			$aliasMountMemberships = $this->generateAlias($aliasMount, self::MEMBERSHIPS, $options);
		}

		$this->leftJoin(
			$aliasMountMemberships, CoreRequestBuilder::TABLE_MOUNTPOINT, $aliasMountpoint,
			$expr->andX(
				$expr->eq($aliasMountpoint . '.mount_id', $aliasMount . '.mount_id'),
				$expr->eq($aliasMountpoint . '.single_id', $aliasMountMemberships . '.single_id')
			)
		);

		$this->selectAlias($aliasMountpoint . '.mountpoint', $aliasMountpoint . '_mountpoint');
		$this->selectAlias($aliasMountpoint . '.mountpoint_hash', $aliasMountpoint . '_mountpoint_hash');
	}


	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return CoreQueryBuilder
	 */
	private function generateCircleSelectAlias(string $alias, array $default = []): self {
		$this->generateSelectAlias(
			CoreRequestBuilder::$tables[CoreRequestBuilder::TABLE_CIRCLE],
			$alias,
			$alias,
			$default
		);

		return $this;
	}

	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateMemberSelectAlias(string $alias, array $default = []): self {
		$this->generateSelectAlias(
			CoreRequestBuilder::$tables[CoreRequestBuilder::TABLE_MEMBER],
			$alias,
			$alias,
			$default
		);

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
		$this->generateSelectAlias(
			CoreRequestBuilder::$tables[CoreRequestBuilder::TABLE_MEMBERSHIP],
			$alias,
			($prefix === '') ? $alias : $prefix,
			$default
		);

		return $this;
	}


	/**
	 * @param string $alias
	 * @param array $default
	 *
	 * @return $this
	 */
	private function generateRemoteInstanceSelectAlias(string $alias, array $default = []): self {
		$this->generateSelectAlias(
			CoreRequestBuilder::$tables[CoreRequestBuilder::TABLE_REMOTE],
			$alias,
			$alias,
			$default
		);

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


	/**
	 * @param array $aliases
	 * @param int $level
	 *
	 * @return ICompositeExpression
	 */
	private function orXCheckLevel(array $aliases, int $level): ICompositeExpression {
		$expr = $this->expr();
		$orX = $expr->orX();
		foreach ($aliases as $alias) {
			$orX->add($expr->gte($alias . '.level', $this->createNamedParameter($level)));
		}

		return $orX;
	}
}
