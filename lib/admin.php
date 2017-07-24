<?php

namespace OCA\Circles;
use OCP\AppFramework\Http\TemplateResponse;

$app = new AppInfo\Application();

/** @var TemplateResponse $response */
$response = $app->getContainer()
				->query('SettingsController')
				->admin();

return $response->render();


