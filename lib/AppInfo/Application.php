<?php

namespace OCA\Circles\AppInfo;

use \OCA\Circles\Controller\NavigationController;
use \OCA\Circles\Controller\CirclesController;

//use \OCA\Circles\Service\ConfigService;
//use \OCA\Circles\Service\ApiService;
use \OCA\Circles\Service\MiscService;
use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

	/** @var string */
	private $appName;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct('circles', $params);

		$container = $this->getContainer();
		$this->appName = $container->query('AppName');

		/**
		 * Services
		 */
		$container->registerService(
			'MiscService', function ($c) {
			return new MiscService($c->query('Logger'), $c->query('AppName'));
		}
		);


//		$container->registerService(
//			'ConfigService', function ($c) {
//			return new ConfigService(
//				$c->query('AppName'), $c->query('CoreConfig'), $c->query('UserId'),
//				$c->query('MiscService')
//			);
//		}
//		);

//		$container->registerService(
//			'ApiService', function ($c) {
//			return new ApiService(
//				$c->query('ConfigService'), $c->query('FileService'), $c->query('MiscService')
//			);
//		}
//		);


		/**
		 * Controllers
		 */
//		$container->registerService(
//			'SettingsController', function ($c) {
//			return new SettingsController(
//				$c->query('AppName'), $c->query('Request'), $c->query('ConfigService'),
//				$c->query('MiscService')
//			);
//		}
//		);

		$container->registerService(
			'NavigationController', function ($c) {
			return new NavigationController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'CirclesController', function ($c) {
			return new CirclesController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'), $c->query('L10N'),
				$c->query('MiscService')
			);
		}
		);

		/**
		 * Mapper
		 */
//		$container->registerService(
//			'DepositionFilesMapper', function ($c) {
//			return new DepositionFilesMapper(
//				$c->query('ServerContainer')
//				  ->getDatabaseConnection()
//			);
//		}
//		);

		// Translates
		$container->registerService(
			'L10N', function ($c) {
			return $c->query('ServerContainer')
					 ->getL10N($c->query('AppName'));
		}
		);

		/**
		 * Core
		 */
		$container->registerService(
			'Logger', function ($c) {
			return $c->query('ServerContainer')
					 ->getLogger();
		}
		);
		$container->registerService(
			'CoreConfig', function ($c) {
			return $c->query('ServerContainer')
					 ->getConfig();
		}
		);

		$container->registerService(
			'UserId', function ($c) {
			$user = $c->query('ServerContainer')
					  ->getUserSession()
					  ->getUser();

			return is_null($user) ? '' : $user->getUID();
		}
		);

		$container->registerService(
			'UserManager', function ($c) {
			return $c->query('ServerContainer')
					 ->getUserManager();
		}
		);
	}


	public function registerNavigation() {

		$this->getContainer()
			 ->getServer()
			 ->getNavigationManager()
			 ->add(
				 function () {
					 return [
						 'id'    => $this->appName,
						 'order' => 5,
						 'href'  => \OC::$server->getURLGenerator()
												->linkToRoute('circles.Navigation.navigate'),
						 'icon'  => \OC::$server->getURLGenerator()
												->imagePath($this->appName, 'circles.svg'),
						 'name'  => \OC::$server->getL10N($this->appName)
												->t('Circles')
					 ];
				 }
			 );
	}


//
//	public function registerSettingsAdmin() {
//		\OCP\App::registerAdmin(
//			$this->getContainer()
//				 ->query('AppName'), 'lib/admin'
//		);
//	}
}

