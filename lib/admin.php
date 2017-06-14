<?php

namespace OCA\Circles;
$app = new \OCA\Circles\AppInfo\Application();

$response = $app->getContainer()
				->query('SettingsController')
				->admin();

return $response->render();
