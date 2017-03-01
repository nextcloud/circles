<?php
/**
 * Circles - bring cloud-users closer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Controller;

use \OCA\Circles\Service\MiscService;
use \OCA\Circles\Service\ConfigService;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Exceptions\TeamDoesNotExists;
use \OCA\Circles\Exceptions\TeamExists;
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
	/** @var ConfigService */
	private $configService;
	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName,
		IRequest $request,
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
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

		$data = [
			'allowed_circles' => array(
				Circle::CIRCLES_PERSONAL => $this->configService->isCircleAllowed(
					Circle::CIRCLES_PERSONAL
				),
				Circle::CIRCLES_HIDDEN   => $this->configService->isCircleAllowed(
					Circle::CIRCLES_HIDDEN
				),
				Circle::CIRCLES_PRIVATE  => $this->configService->isCircleAllowed(
					Circle::CIRCLES_PRIVATE
				),
				Circle::CIRCLES_PUBLIC   => $this->configService->isCircleAllowed(
					Circle::CIRCLES_PUBLIC
				),
			)
		];


		return new TemplateResponse(
			'circles', 'navigate', $data
		);
	}


}