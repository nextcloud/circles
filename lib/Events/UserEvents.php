<?php


namespace OCA\Circles\Events;


use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCP\IUser;
use OCA\Circles\AppInfo\Application;
use OC\Files\View;
use OCA\Circles\Api\v1\Circles;

class UserEvents {

	/** @var CirclesService */
	private $circlesService;

	/** @var MembersService */
	private $membersService;

	/** @var GroupsService */
	private $groupsService;

	/** @var MiscService */
	private $miscService;
	
	/** @var IUser */
	private static $user;

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
	 *
	 * @param array $params
	 */
	public function onItemShared(array $params) {
		$shareWith = $params['shareWith'];
		$fileTarget = $params['fileTarget'];
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user shared $fileTarget with $shareWith");
		try {
			$path = Circles::getViewPath($params['nodeId']);
		} catch (NotFoundException $e) {
			return;
		}
		$subjectParams = [
			'author' => [
				'id'   => $this->getUser()->getUID(),
				'name' => $user 
			],	
			'circle' => [
				'name' => $shareWith
			],
			'file' => [
				'id'   => $params['nodeId'],
				'name' => $fileTarget,
				'type' => $params['itemType']
			]
		];
		$objectType = ($params ['id']) ? 'files' : '';
		$link = \OC::$server->getURLGenerator ()->linkToRouteAbsolute('files.view.index', array (
				'dir' => ($params['itemType'] !== 'file') ? dirname($path) : $path 
		) );
		$event = \OC::$server->getActivityManager()->generateEvent('shared');
		$event->setApp('files_sharing')
			->setType('shared')
			->setAffectedUser($this->getUser()->getDisplayName())
			->setTimestamp(time())
			->setSubject('shared_circle_self', $subjectParams)
			->setParsedSubject("$user shared $fileTarget with the circle $circle")
			->setObject($objectType,(int) $params ['id'], $fileTarget )
			->setLink($link);
		\OC::$server->getActivityManager()->publish($event);
	}
	
	/**
	 *
	 * @param array $params
	 */
	public function onItemUnshared(array $params) {
		$shareWith = $params ['shareWith'];
		$fileTarget = $params ['fileTarget'];
		$user = $this->getUser ()->getDisplayName ();
		if (! empty ( $shareWith )) {
			if (ctype_alnum ( $shareWith )) {
				try {
					$shareWith = $this->circlesService->detailsCircle ( $shareWith )->getName ();
				} catch ( \Exception $e ) {
				}
			}
		}
		$this->miscService->log ( "user $user unshared $fileTarget with $shareWith");
		try {
			$path = Circles::getViewPath($params['nodeId']);
		} catch ( NotFoundException $e ) {
			return;
		}
		$subjectParams = [
			'author' => [
				'id'   => $this->getUser()->getUID(),
				'name' => $user 
			],	
			'circle' => [
				'name' => $shareWith
			],
			'file' => [
				'id'   => $params['nodeId'],
				'name' => $fileTarget,
				'type' => $params['itemType']
			]
		];
		$objectType = ($params ['id']) ? 'files' : '';
		$link = \OC::$server->getURLGenerator ()->linkToRouteAbsolute ( 'files.view.index', array (
				'dir' => ($params ['itemType'] !== 'file') ? dirname ( $path ) : $path 
		) );
		$event = \OC::$server->getActivityManager ()->generateEvent ('shared');
		$event->setApp('files_sharing')
			->setType('shared')
			->setAffectedUser($this->getUser()->getDisplayName())
			->setTimestamp(time())
			->setSubject('unshared_circle_self', $subjectParams )
			->setParsedSubject("$user unshared $fileTarget with the circle $shareWith")
			->setObject($objectType,(int)$params ['id'], $params ['fileTarget'])
			->setLink ( $link );
		\OC::$server->getActivityManager ()->publish ( $event );
	}
	
	/**
	 *
	 * @return User
	 */
	private function getUser() {
		if (self::$user == null) {
			$app = new Application();
			self::$user = $app->getContainer()->query('UserSession')->getUser();
		}
		return self::$user;
	}
}

