<?php


namespace OCA\Circles\Hooks;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\UserEvents;


class UserHooks {

	static protected function getController() {
		$app = new Application();

		return $app->getContainer()
				   ->query(UserEvents::class);
	}


	public static function onUserDeleted($params) {
		self::getController()
			->onUserDeleted($params);
	}


	public static function onGroupDeleted($params) {
		self::getController()
			->onGroupDeleted($params);
	}

	public static function onCircleCreated($params) {
		self::getController()
		->onCircleCreated($params);
	}

	public static function onCircleDestroyed($params) {
		self::getController()
		->onCircleDestroyed($params);
	}

	public static function onCircleUpdated($params) {
		self::getController()
		->onCircleUpdated($params);
	}

	public static function onMemberAdded($params) {
		self::getController()
		->onMemberAdded($params);
	}

	public static function onMemberRemoved($params) {
		self::getController()
		->onMemberRemoved($params);
	}
	
	public static function onItemShared($params) {
		self::getController()
		->onItemShared($params);
	}
	
	public static function onItemUnshared($params) {
		self::getController()
		->onItemUnshared($params);
	}
}