<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;

class UserEvents {

	/** @var CirclesService */
	private $circlesService;

	/** @var MembersService */
	private $membersService;

	/** @var GroupsService */
	private $groupsService;

	/** @var MiscService */
	private $miscService;

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

}

