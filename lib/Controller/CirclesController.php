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

class CirclesController extends Controller {

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
			'circles', 'navigate', [
					 ]
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
//	public function create($name) {
//		try {
//			$id = $this->dbHandler->createTeam($name, $this->userId);
//		} catch (TeamExists $e) {
//			return new DataResponse(
//				[
//					'message' => (string)$this->l10n->t('Team already exists.')
//				],
//				Http::STATUS_CONFLICT
//			);
//		}
//		return new DataResponse(
//			[
//				'id' => $id,
//				'name' => $name,
//				'owner' => $this->userId,
//			],
//			Http::STATUS_CREATED
//		);
//	}

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