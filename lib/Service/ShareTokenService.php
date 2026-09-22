<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Entity\ShareToken;
use OCA\Circles\Exceptions\ShareTokenAlreadyExistException;
use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Repository\ShareTokenRepository;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IURLGenerator;
use OCP\Share\IShare;

/**
 * Class ShareTokenService
 *
 * @package OCA\Circles\Service
 */
class ShareTokenService {
	use TStringTools;

	/**
	 * ShareTokenService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param ShareTokenRepository $shareTokenRepository
	 * @param ConfigService $configService
	 */
	public function __construct(
		private IURLGenerator $urlGenerator,
		private ShareTokenRepository $shareTokenRepository,
		private ConfigService $configService,
	) {
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
		string $hashedPassword = '',
	): ShareToken {
		if ($member->getUserType() !== Member::TYPE_MAIL
			&& $member->getUserType() !== Member::TYPE_CONTACT) {
			throw new ShareTokenNotFoundException();
		}

		try {
			$this->shareTokenRepository->findOneBy([
				'shareId' => (int)$share->getId(),
				'circleId' => $share->getSharedWith(),
				'singleId' => $member->getSingleId(),
			]);
			throw new ShareTokenAlreadyExistException();
		} catch (DoesNotExistException) {
		}

		$entity = new ShareToken();
		$entity->shareId = (int)$share->getId();
		$entity->circleId = $share->getSharedWith();
		$entity->singleId = $member->getSingleId();
		$entity->memberId = $member->getId();
		$entity->token = $this->token(19);
		$entity->password = $hashedPassword;
		$entity->accepted = IShare::STATUS_ACCEPTED;

		return $this->shareTokenRepository->insert($entity);
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

		$this->shareTokenRepository->updateSharePassword($circleId, $hashedPassword);
	}

	/**
	 * remove password on files previously shared to circleId
	 *
	 * @param string $circleId
	 */
	public function removeSharePassword(string $circleId): void {
		$this->shareTokenRepository->updateSharePassword($circleId, '');
	}

	/**
	 * @param string $singleId
	 * @param string $circleId
	 */
	public function removeTokens(string $singleId, string $circleId) {
		$this->shareTokenRepository->deleteBy([
			'singleId' => $singleId,
			'circleId' => $circleId,
		]);
	}

	/**
	 * @param array $shareIds
	 *
	 * @return \Generator<ShareToken>
	 */
	public function getTokensFromShares(array $shareIds): \Generator {
		if ($shareIds === []) {
			return;
		}

		yield from $this->shareTokenRepository->findBy(['shareId' => $shareIds]);
	}
}
