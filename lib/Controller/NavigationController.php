<?php


namespace OCA\Circles\Controller;

use OCA\Circles\Service\MiscService;
use OCA\Circles\Exceptions\TeamDoesNotExists;
use OCA\Circles\Exceptions\TeamExists;
use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

class NavigationController extends Controller {

	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;
	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName,
		IRequest $request,
		$userId,
		IL10N $l10n,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 */
	public function navigate() {

		return new TemplateResponse(
			'circles', 'navigate', []
		);
	}


}