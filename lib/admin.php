<?php

namespace OCA\Circles;
use OCA\Circles\Controller\SettingsController;
use OCP\AppFramework\Http\TemplateResponse;

$app = new AppInfo\Application();

/** @var TemplateResponse $response */
$response = $app->getContainer()
				->query(SettingsController::class)
				->admin();

return $response->render();


