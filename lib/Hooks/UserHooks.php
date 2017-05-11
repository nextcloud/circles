<?php


namespace OCA\Circles\Hooks;

use OCA\Circles\AppInfo\Application;


class UserHooks {

	static protected function getController() {
		$app = new Application();

		return $app->getContainer()
				   ->query('UserEvents');
	}


	public static function onUserCreated($params) {
		self::getController()
			->onUserCreated($params);
	}


	public static function onUserDeleted($params) {
		self::getController()
			->onUserDeleted($params);
	}

}

