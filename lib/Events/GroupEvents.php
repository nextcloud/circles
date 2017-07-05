<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MiscService;

class GroupEvents {

	/** @var GroupsService */
	private $groupsService;

	/** @var MiscService */
	private $miscService;

	public function __construct(GroupsService $groupsService, MiscService $miscService) {
		$this->groupsService = $groupsService;
		$this->miscService = $miscService;
	}


	/**
	 * @param array $params
	 */
	public function onGroupDeleted(array $params) {
		$groupId = $params['gid'];
		$this->groupsService->unlinkGroup($groupId);
	}

}

