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

namespace OCA\Circles\Service;


use Exception;
use OC\Http\Client\ClientService;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\FederatedCircleLinkFormatException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\CircleTypeNotValid;
use OCA\Circles\Exceptions\FederatedRemoteCircleDoesNotExistException;
use OCA\Circles\Exceptions\FederatedRemoteDoesNotAllowException;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCP\IL10N;
use Sabre\HTTP\ClientException;

class FederatedService {


	const STATUS_ERROR = 0;
	const STATUS_LINK_DOWN = 1;
	const STATUS_REQUEST_DECLINED = 3;
	const STATUS_REQUEST_SENT = 6;
	const STATUS_LINK_UP = 9;


	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesService */
	private $circlesService;

	/** @var CirclesMapper */
	private $dbCircles;

	/** @var MembersMapper */
	private $dbMembers;

	/** @var ClientService */
	private $clientService;

	/** @var MiscService */
	private $miscService;


	/**
	 * CirclesService constructor.
	 *
	 * @param $userId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param DatabaseService $databaseService
	 * @param CirclesService $circlesService
	 * @param ClientService $clientService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		DatabaseService $databaseService,
		CirclesService $circlesService,
		ClientService $clientService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->clientService = $clientService;
		$this->miscService = $miscService;

		$this->dbCircles = $databaseService->getCirclesMapper();
		$this->dbMembers = $databaseService->getMembersMapper();
	}


	/**
	 * link to a circle.
	 *
	 * @param int $circleId
	 * @param string $link
	 *
	 * @return bool
	 * @throws Exception
	 * @throws FederatedCircleLinkFormatException
	 * @throws CircleTypeNotValid
	 * @throws MemberIsNotAdminException
	 */
	public function linkCircle($circleId, $link) {

		if (!$this->configService->isFederatedAllowed()) {
			throw new FederatedCircleNotAllowedException(
				$this->l10n->t("Federated circles are not allowed on this Nextcloud")
			);
		}

		if (strpos($link, '@') === false) {
			throw new FederatedCircleLinkFormatException(
				$this->l10n->t("Federated link does not have a valid format")
			);
		}

		list($remoteCircle, $remoteAddress) = explode('@', $link, 2);
		try {

			$circle = $this->circlesService->detailsCircle($circleId);
			$circle->getUser()
				   ->hasToBeAdmin();
			$circle->cantBePersonal();

			return $this->requestLinkWithCircle($circle, $remoteAddress, $remoteCircle);
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param string $remote
	 *
	 * @return string
	 */
	private function generateLinkRemoteURL($remote) {
		if (strpos($remote, 'http') !== 0) {
			$remote = 'https://' . $remote;
		}

		return rtrim($remote, '/') . '/index.php/apps/circles/circles/link/';
	}


	/**
	 * @param Circle $circle
	 * @param $remoteAddress
	 * @param $remoteCircle
	 *
	 * @return integer
	 * @throws Exception
	 */
	private function requestLinkWithCircle(Circle $circle, $remoteAddress, $remoteCircle) {
		$this->miscService->log(
			"create link : " . $remoteCircle . ' - ' . $remoteAddress . ' - ' . $circle->getId()
		);

		$args = [
			'sourceId'   => $circle->getId(),
			'sourceName' => $circle->getName(),
			'circleName' => $remoteCircle
		];

		$client = $this->clientService->newClient();
		try {
			$request = $client->put(
				$this->generateLinkRemoteURL($remoteAddress), [
																'body'            => $args,
																'timeout'         => 10,
																'connect_timeout' => 10,
															]
			);

			$result = json_decode($request->getBody(), true);
			$this->requestLinkStatus($result);

			$this->miscService->log("_____RESULT: " . var_export($result, true));

			return $result['status'];
		} catch (Exception $e) {
			throw $e;
		}
	}


	private function requestLinkStatus($result) {

		if ($result['status'] === self::STATUS_REQUEST_SENT
			|| $result['status'] === self::STATUS_LINK_UP
		) {
			return;
		}

		if ($result['reason'] === 'federated_not_allowed') {
			throw new FederatedRemoteDoesNotAllowException(
				$this->l10n->t('Federated circles are not allowed on the remote Nextcloud')
			);
		}

		if ($result['reason'] === 'circle_does_not_exist') {
			throw new FederatedRemoteCircleDoesNotExistException(
				$this->l10n->t('The requested remote circle does not exist')
			);
		}

		throw new Exception();
	}


	/**
	 * @param Circle $circle
	 * @param $source
	 * @param FederatedLink $link
	 *
	 * @return bool
	 */
	public function initiateLink(Circle $circle, FederatedLink &$link) {

		$token = '';
		for ($i = 0; $i <= 5; $i++) {
			$token .= uniqid('', true);
		}

		$link->setToken($token);

//		$link->setStatus(
//			($circle->getType()
//			 === Circle::CIRCLES_PUBLIC) ? FederatedService::STATUS_LINK_UP : FederatedService::STATUS_REQUEST_SENT
//		);
//($link->isLinkUp());
		return true;
	}

}