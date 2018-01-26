<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCP\Util;
use OC\User\User;
use OC\Log;
use OCA\Circles\AppInfo\Application;

class UserEvents {
	
	/** Default for warning log level **/
	const logLevel = 2;

	/** @var CirclesService */
	private $circlesService;

	/** @var MembersService */
	private $membersService;

	/** @var GroupsService */
	private $groupsService;

	/** @var MiscService */
	private $miscService;
	
	/** @var User */
	private static $user = null;

	/**
	 * UserEvents constructor.
	 *
	 * @param CirclesService $circlesService
	 * @param MembersService $membersService
	 * @param GroupsService $groupsService
	 * @param MiscService $miscService
	 */
	public function __construct(
		CirclesService $circlesService, MembersService $membersService, GroupsService $groupsService,
		MiscService $miscService
	) {
		$this->circlesService = $circlesService;
		$this->membersService = $membersService;
		$this->groupsService = $groupsService;
		$this->miscService = $miscService;
	}


	/**
	 * @param array $params
	 */
	public function onUserDeleted(array $params) {
		$userId = $params['uid'];
		$this->circlesService->onUserRemoved($userId);
		$this->membersService->onUserRemoved($userId);
	}


	/**
	 * @param array $params
	 */
	public function onGroupDeleted(array $params) {
		$groupId = $params['gid'];
		$this->groupsService->onGroupRemoved($groupId);
	}
	
	/**
	 * @param array $params
	 */
	public function onCircleCreated(array $params) {
		$circle = $params['circle'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user created circle $circle", self::logLevel);
	}

	/**
	 * @param array $params
	 */
	public function onCircleDestroyed(array $params) {
		$circle = $params['circle'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user destroyed circle $circle", self::logLevel);
	}

	/**
	 * @param array $params
	 */
	public function onCircleUpdated(array $params) {
		$formerCircle = $params['former_name'];
		$circle = $params['circle_name'];
		if ($formerCircle != $circle){
			$user = $this->getUser()->getDisplayName();
			$this->miscService->log("user $user updated circle $formerCircle to $circle", self::logLevel);
		}
	}

	/**
	 * @param array $params
	 */
	public function onMemberAdded(array $params) {
		$circle = $params['circle'];
		$member = $params['member'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user added member $member to circle $circle", self::logLevel);
	}

	/**
	 * @param array $params
	 */
	public function onMemberRemoved(array $params) {
		$circle = $params['circle'];
		$member = $params['member'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user removed member $member from circle $circle", self::logLevel);
	}

	/**
	 * @param array $params
	 */
	public function onItemShared(array $params) {
		$shareWith = $params['shareWith'];
		$fileTarget = $params['fileTarget'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user shared $fileTarget with $shareWith", self::logLevel);
	}

	/**
	 * @param array $params
	 */
	public function onItemUnshared(array $params) {
		$shareWith = $params['shareWith'];
		$fileTarget = $params['fileTarget'];
		$user = $this->getUser()->getDisplayName();
		if (!empty($shareWith)){
			$this->miscService->log("user $user unshared $fileTarget with $shareWith", self::logLevel);
		}
	}

	/**
	 * @return User
	 */
	private function getUser()
	{
		if (self::$user == null){
			$app = new Application();
			self::$user = $app->getContainer()->query('UserSession')->getUser();
		}
		return self::$user;
	}
}