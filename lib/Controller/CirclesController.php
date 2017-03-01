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
use \OCA\Circles\Service\CirclesService;
use \OCA\Circles\Model\iError;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;


use \OCA\Circles\Exceptions\TeamDoesNotExists;
use \OCA\Circles\Exceptions\TeamExists;
use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

class CirclesController extends Controller {

	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;
	/** @var ConfigService */
	private $configService;
	/** @var CirclesService */
	private $circlesService;
	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName,
		IRequest $request,
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		CirclesService $circlesService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function create($type, $name) {

		$data = null;
		if (substr($name, 0, 1) === '_') {
			$iError = new iError();
			$iError->setCode(iError::CIRCLE_CREATION_FIRST_CHAR)
				   ->setMessage("The name of your circle cannot start with this character");
		} else {
			$data = $this->circlesService->createCircle($type, $name, $iError);
		}

		if ($data === null) {
			return
				new DataResponse(
					[
						'name'   => $name,
						'type'   => $type,
						'status' => 0,
						'error'  => $iError->toArray()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);
		}

		return new DataResponse(
			[
				'name'   => $name,
				'circle' => $data,
				'type'   => $type,
				'status' => 1
			], Http::STATUS_CREATED
		);

	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function list($type, $name = '') {

		$data = $this->circlesService->listCircles($type, $name, Member::LEVEL_NONE, $iError);

		if ($data === null) {
			return
				new DataResponse(
					[
						'type'   => $type,
						'status' => 0,
						'error'  => $iError->toArray()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);

		}

		return new DataResponse(
			[
				'type'   => $type,
				'data'   => $data,
				'status' => 1
			], Http::STATUS_CREATED
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function details($id) {

		$data = $this->circlesService->detailsCircle($id, $iError);

		if ($data === null) {
			return
				new DataResponse(
					[
						'circle_id' => $id,
						'status'    => 0,
						'error'     => $iError->toArray()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);

		}

		return new DataResponse(
			[
				'circle_id' => $id,
				'details'   => $data,
				'status'    => 1
			], Http::STATUS_CREATED
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function join($id) {

		$data = $this->circlesService->joinCircle($id, $iError);

		if ($data === null) {
			return
				new DataResponse(
					[
						'circle_id' => $id,
						'status'    => 0,
						'error'     => $iError->toArray()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);

		}

		return new DataResponse(
			[
				'circle_id' => $id,
				'member'    => $data,
				'status'    => 1
			], Http::STATUS_CREATED
		);
	}






	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function leave($id) {

		$data = $this->circlesService->leaveCircle($id, $iError);

		if ($data === null) {
			return
				new DataResponse(
					[
						'circle_id' => $id,
						'status'    => 0,
						'error'     => $iError->toArray()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);

		}

		return new DataResponse(
			[
				'circle_id' => $id,
				'member'    => $data,
				'status'    => 1
			], Http::STATUS_CREATED
		);
	}








	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 *
	 * @return DataResponse
	 */
//	public function delete($id) {
//		$affectedRows = $this->dbHandler->deleteTeam($id, $this->userId);
//
//		if ($affectedRows === 1) {
//			return new DataResponse(
//				[],
//				Http::STATUS_NO_CONTENT
//			);
//		}
//
//		return new DataResponse(
//			[
//				'message' => (string)$this->l10n->t('Unable to delete team.')
//			],
//			Http::STATUS_INTERNAL_SERVER_ERROR
//		);
//	}


}

