<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;

class UserEvents {

	/** @var MembersService */
	private $membersService;

	/** @var MiscService */
	private $miscService;

	public function __construct(MembersService $membersService, MiscService $miscService) {
		$this->membersService = $membersService;
		$this->miscService = $miscService;
	}


	/**
	 * @param array $params
	 */
	public function onUserDeleted(array $params) {
		$userId = $params['uid'];
		$this->membersService->onUserRemoved($userId);
	}

}

