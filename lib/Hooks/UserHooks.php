<?php


namespace OCA\Circles\Hooks;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\UserEvents;


class UserHooks {

	static protected function getController() {
		$app = \OC::$server->query(Application::class);

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

}

