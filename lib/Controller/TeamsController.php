<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
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

namespace OCA\Teams\Controller;

use OC\AppFramework\Http;
use OCA\Teams\DatabaseHandler;
use OCA\Teams\Exceptions\TeamDoesNotExists;
use OCA\Teams\Exceptions\TeamExists;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

class TeamsController extends Controller {


	/** @var DatabaseHandler */
	private $dbHandler;
	/** @var ILogger */
	private $logger;
	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;

	public function __construct($appName,
								IRequest $request,
								DatabaseHandler $dbHandler,
								ILogger $logger,
								$userId,
								IL10N $l10n) {
		parent::__construct($appName, $request);

		$this->dbHandler = $dbHandler;
		$this->logger = $logger;
		$this->userId = $userId;
		$this->l10n = $l10n;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 */
	public function show() {

		return new TemplateResponse('teams', 'show', [
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 * @return DataResponse
	 */
	public function create($name) {
		try {
			$id = $this->dbHandler->createTeam($name, $this->userId);
		} catch (TeamExists $e) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('Team already exists.')
				],
				Http::STATUS_CONFLICT
			);
		}
		return new DataResponse(
			[
				'id' => $id,
				'name' => $name,
				'owner' => $this->userId,
			],
			Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $name
	 * @return DataResponse
	 */
	public function rename($id, $name) {

		$affectedRows = $this->dbHandler->updateTeam($id, $this->userId, $name);

		if ($affectedRows === 1) {
			return new DataResponse(
				[
					'id' => $id,
					'name' => $name,
					'owner' => $this->userId,
				],
				Http::STATUS_OK
			);
		}

		return new DataResponse(
			[
				'message' => (string)$this->l10n->t('Unable to update team name.')
			],
			Http::STATUS_FORBIDDEN
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function delete($id) {
		$affectedRows = $this->dbHandler->deleteTeam($id, $this->userId);

		if ($affectedRows === 1) {
			return new DataResponse(
				[],
				Http::STATUS_NO_CONTENT
			);
		}

		return new DataResponse(
			[
				'message' => (string)$this->l10n->t('Unable to delete team.')
			],
			Http::STATUS_INTERNAL_SERVER_ERROR
		);
	}
	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 */
	public function listTeams() {
		$myTeams = $this->dbHandler->getTeamsByAdmin($this->userId);
		$otherTeams = $this->dbHandler->getTeamsByMember($this->userId);

		return new DataResponse(
			[
				'myTeams' => $myTeams,
				'otherTeams' => $otherTeams,
			],
			Http::STATUS_OK
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function listMembers($id) {
		if (!(
			$this->dbHandler->isOwner($id, $this->userId) ||
			$this->dbHandler->isMember($id, $this->userId)
			)) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('User is not owner nor member of the team.')
				],
				Http::STATUS_FORBIDDEN
			);
		}

		try {
			$members = $this->dbHandler->getMembers($id);
			return new DataResponse(
				[
					'members' => $members,
				],
				Http::STATUS_OK
			);
		} catch (TeamDoesNotExists $e){
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('Team does not exist.')
				],
				Http::STATUS_NOT_FOUND
			);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $userId user id of new member
	 * @return DataResponse
	 */
	public function addMember($id, $userId) {
		if (!$this->dbHandler->isOwner($id, $this->userId)) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('User is not owner of the team.')
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->dbHandler->addMember($id, $userId);
		return new DataResponse(
			[],
			Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $userId user id of member
	 * @return DataResponse
	 */
	public function removeMember($id, $userId) {
		if (!$this->dbHandler->isOwner($id, $this->userId)) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('User is not owner of the team.')
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$affectedRows = $this->dbHandler->removeMember($id, $userId);

		if ($affectedRows === 1) {
			return new DataResponse(
				[],
				Http::STATUS_NO_CONTENT
			);
		}

		return new DataResponse(
			[
				'message' => (string)$this->l10n->t('Unable to remove team member.') . $affectedRows
			],
			Http::STATUS_INTERNAL_SERVER_ERROR
		);
	}
}