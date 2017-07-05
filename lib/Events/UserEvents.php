<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;

class UserEvents {

	/** @var MembersService */
	private $membersService;

	/** @var GroupsService */
	private $groupsService;

	/** @var MiscService */
	private $miscService;

	public function __construct(
		MembersService $membersService, GroupsService $groupsService, MiscService $miscService
	) {
		$this->membersService = $membersService;
		$this->groupsService = $groupsService;
		$this->miscService = $miscService;
	}


	/**
	 * @param array $params
	 */
	public function onUserDeleted(array $params) {
		$userId = $params['uid'];
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

