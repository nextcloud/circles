<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use OCA\Circles\Db\ShareTokenRequest;
use OCA\Circles\Exceptions\ShareTokenAlreadyExistException;
use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareToken;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\IURLGenerator;
use OCP\Share\IShare;

/**
 * Class ShareTokenService
 *
 * @package OCA\Circles\Service
 */
class ShareTokenService {
	use TStringTools;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ShareTokenRequest */
	private $shareTokenRequest;

	/** @var ConfigService */
	private $configService;

	/** @var InterfaceService */
	private $interfaceService;


	/**
	 * ShareTokenService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param ShareTokenRequest $shareTokenRequest
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		ShareTokenRequest $shareTokenRequest,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->shareTokenRequest = $shareTokenRequest;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * @param ShareWrapper $share
	 * @param Member $member
	 * @param string $hashedPassword
	 *
	 * @return ShareToken
	 * @throws ShareTokenAlreadyExistException
	 * @throws ShareTokenNotFoundException
	 */
	public function generateShareToken(
		ShareWrapper $share,
		Member $member,
		string $hashedPassword = ''
	): ShareToken {
		if ($member->getUserType() !== Member::TYPE_MAIL
			&& $member->getUserType() !== Member::TYPE_CONTACT) {
			throw new ShareTokenNotFoundException();
		}

		$token = $this->token(19);
		$shareToken = new ShareToken();
		$shareToken->setShareId((int)$share->getId())
				   ->setCircleId($share->getSharedWith())
				   ->setSingleId($member->getSingleId())
				   ->setMemberId($member->getId())
				   ->setToken($token)
				   ->setPassword($hashedPassword)
				   ->setAccepted(IShare::STATUS_ACCEPTED);

		try {
			$this->shareTokenRequest->search($shareToken);
			throw new ShareTokenAlreadyExistException();
		} catch (ShareTokenNotFoundException $e) {
		}

		$this->shareTokenRequest->save($shareToken);
		$this->setShareTokenLink($shareToken);

		return $shareToken;
	}


	/**
	 * @param ShareToken $shareToken
	 */
	public function setShareTokenLink(ShareToken $shareToken): void {
		$link = $this->interfaceService->getFrontalPath(
			'files_sharing.sharecontroller.showShare',
			['token' => $shareToken->getToken()]
		);

		$shareToken->setLink($link);
	}


	/**
	 * update password on files previously shared to circleId
	 *
	 * @param string $circleId
	 * @param string $hashedPassword
	 */
	public function updateSharePassword(string $circleId, string $hashedPassword): void {
		if ($hashedPassword === '') {
			return;
		}

		$this->shareTokenRequest->updateSharePassword($circleId, $hashedPassword);
	}

	/**
	 * remove password on files previously shared to circleId
	 *
	 * @param string $circleId
	 */
	public function removeSharePassword(string $circleId): void {
		$this->shareTokenRequest->updateSharePassword($circleId, '');
	}

	/**
	 * @param string $singleId
	 * @param string $circleId
	 */
	public function removeTokens(string $singleId, string $circleId) {
		$this->shareTokenRequest->removeTokens($singleId, $circleId);
	}

	/**
	 * @param array $shareIds
	 *
	 * @return ShareToken[]
	 */
	public function getTokensFromShares(array $shareIds): array {
		return ($shareIds === []) ? [] : $this->shareTokenRequest->getTokensFromShares($shareIds);
	}
}
