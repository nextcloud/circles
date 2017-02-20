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
	public function create($name, $type) {

		if (substr($name, 0, 1) === '_') {
			$iError = new iError();
			$iError->setCode(iError::CIRCLE_CREATION_FIRST_CHAR)
				   ->setMessage("The name of your circle cannot start with this character");
			$result = [
				'name'   => $name,
				'type'   => $type,
				'status' => 0,
				'error'  => $iError->toArray()
			];
		} else {
			$result = $this->circlesService->createCircle($name, $type);
		}

		if ($result['status'] === 1) {
			$status = Http::STATUS_CREATED;
		} else {
			$status = Http::STATUS_NON_AUTHORATIVE_INFORMATION;
		}

		return new DataResponse(
			$result,
			$status
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
	public function listCircles($type) {

		$result = $this->circlesService->listCircles($type);

		if ($result['status'] === 1) {
			$status = Http::STATUS_CREATED;
		} else {
			$status = Http::STATUS_NON_AUTHORATIVE_INFORMATION;
		}

		return new DataResponse($result, $status);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function detailsCircle($id) {

		$result = $this->circlesService->detailsCircle($id);

		if ($result['status'] === 1) {
			$status = Http::STATUS_CREATED;
		} else {
			$status = Http::STATUS_NON_AUTHORATIVE_INFORMATION;
		}

		return new DataResponse($result, $status);
	}





	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $name
	 *
	 * @return DataResponse
	 */
//	public function rename($id, $name) {
//
//		$affectedRows = $this->dbHandler->updateTeam($id, $this->userId, $name);
//
//		if ($affectedRows === 1) {
//			return new DataResponse(
//				[
//					'id' => $id,
//					'name' => $name,
//					'owner' => $this->userId,
//				],
//				Http::STATUS_OK
//			);
//		}
//
//		return new DataResponse(
//			[
//				'message' => (string)$this->l10n->t('Unable to update team name.')
//			],
//			Http::STATUS_FORBIDDEN
//		);
//	}


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


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 */
//	public function listTeams() {
//		$myTeams = $this->dbHandler->getTeamsByAdmin($this->userId);
//		$otherTeams = $this->dbHandler->getTeamsByMember($this->userId);
//
//		return new DataResponse(
//			[
//				'myTeams' => $myTeams,
//				'otherTeams' => $otherTeams,
//			],
//			Http::STATUS_OK
//		);
//	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 *
	 * @return DataResponse
	 */
//	public function listMembers($id) {
//		if (!(
//			$this->dbHandler->isOwner($id, $this->userId) ||
//			$this->dbHandler->isMember($id, $this->userId)
//			)) {
//			return new DataResponse(
//				[
//					'message' => (string)$this->l10n->t('User is not owner nor member of the team.')
//				],
//				Http::STATUS_FORBIDDEN
//			);
//		}
//
//		try {
//			$members = $this->dbHandler->getMembers($id);
//			return new DataResponse(
//				[
//					'members' => $members,
//				],
//				Http::STATUS_OK
//			);
//		} catch (TeamDoesNotExists $e){
//			return new DataResponse(
//				[
//					'message' => (string)$this->l10n->t('Team does not exist.')
//				],
//				Http::STATUS_NOT_FOUND
//			);
//		}
//	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $userId user id of new member
	 *
	 * @return DataResponse
	 */
//	public function addMember($id, $userId) {
//		if (!$this->dbHandler->isOwner($id, $this->userId)) {
//			return new DataResponse(
//				[
//					'message' => (string)$this->l10n->t('User is not owner of the team.')
//				],
//				Http::STATUS_FORBIDDEN
//			);
//		}
//
//		$this->dbHandler->addMember($id, $userId);
//		return new DataResponse(
//			[],
//			Http::STATUS_CREATED
//		);
//	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $userId user id of member
	 *
	 * @return DataResponse
	 */
//	public function removeMember($id, $userId) {
//		if (!$this->dbHandler->isOwner($id, $this->userId)) {
//			return new DataResponse(
//				[
//					'message' => (string)$this->l10n->t('User is not owner of the team.')
//				],
//				Http::STATUS_FORBIDDEN
//			);
//		}
//
//		$affectedRows = $this->dbHandler->removeMember($id, $userId);
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
//				'message' => (string)$this->l10n->t('Unable to remove team member.') . $affectedRows
//			],
//			Http::STATUS_INTERNAL_SERVER_ERROR
//		);
//	}
}

