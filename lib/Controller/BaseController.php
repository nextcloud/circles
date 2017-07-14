<?php
/**
 * Circles - Bring cloud-users closer together.
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

use OC\AppFramework\Http;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedService;
use OCA\Circles\Service\GroupsService;
use OCA\Circles\Service\MembersService;

use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

class BaseController extends Controller {

	/** @var string */
	protected $userId;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var CirclesService */
	protected $circlesService;

	/** @var MembersService */
	protected $membersService;

	/** @var GroupsService */
	protected $groupsService;

	/** @var SharesService */
	protected $sharesService;

	/** @var FederatedService */
	protected $federatedService;

	/** @var MiscService */
	protected $miscService;


	/**
	 * BaseController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param string $userId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param CirclesService $circlesService
	 * @param MembersService $membersService
	 * @param GroupsService $groupsService
	 * @param SharesService $sharesService
	 * @param FederatedService $federatedService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName,
		IRequest $request,
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		CirclesService $circlesService,
		MembersService $membersService,
		GroupsService $groupsService,
		SharesService $sharesService,
		FederatedService $federatedService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->membersService = $membersService;
		$this->groupsService = $groupsService;
		$this->sharesService = $sharesService;
		$this->federatedService = $federatedService;
		$this->miscService = $miscService;
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	protected function fail($data) {
		$this->miscService->log(json_encode($data));
		return new DataResponse(
			array_merge($data, array('status' => 0)),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	protected function success($data) {
		return new DataResponse(
			array_merge($data, array('status' => 1)),
			Http::STATUS_CREATED
		);
	}

}