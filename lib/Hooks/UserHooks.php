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

	public static function onItemShared($params) {
		self::getController()
			->onItemShared($params);
	}

	public static function onItemUnshared($params) {
		self::getController()
			->onItemUnshared($params);
	}
}