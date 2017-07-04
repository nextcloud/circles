<?php


namespace OCA\Circles\Hooks;

use OCA\Circles\AppInfo\Application;


class GroupHooks {

	static protected function getController() {
		$app = new Application();

		return $app->getContainer()
				   ->query('GroupEvents');
	}


	public static function onGroupDeleted($params) {
		self::getController()
			->onGroupDeleted($params);
	}

}

