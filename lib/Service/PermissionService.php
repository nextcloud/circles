<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Exceptions\MemberHelperException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCP\IL10N;

class PermissionService {

	public function __construct(
		private readonly IL10N $l10n,
		private readonly FederatedUserService $federatedUserService,
		private readonly ConfigService $configService,
		private readonly MemberRequest $memberRequest,
		private readonly MembershipRequest $membershipRequest,
	) {
	}


	/**
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 * @throws InsufficientPermissionException
	 */
	public function confirmCircleCreation(): void {
		try {
			$this->confirm(ConfigService::LIMIT_CIRCLE_CREATION);
		} catch (InsufficientPermissionException) {
			throw new InsufficientPermissionException(
				$this->l10n->t('You have no permission to create a new team')
			);
		}
	}


	/**
	 * @param string $config
	 *
	 * @throws InsufficientPermissionException
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 */
	private function confirm(string $config): void {
		$singleId = $this->configService->getAppValue($config);
		if ($singleId === '') {
			return;
		}

		$this->federatedUserService->mustHaveCurrentUser();
		$federatedUser = $this->federatedUserService->getCurrentUser();
		try {
			$federatedUser->getLink($singleId);
		} catch (MembershipNotFoundException) {
			throw new InsufficientPermissionException();
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 * @throws RequestBuilderException
	 */
	private function canBypassCircleTypes(Circle $circle): bool {
		try {
			if (!$circle->hasInitiator()) {
				throw new MembershipNotFoundException();
			}

			$circle->getInitiator()->getLink(
				$this->configService->getAppValue(ConfigService::BYPASS_CIRCLE_TYPES)
			);

			return true;
		} catch (MembershipNotFoundException) {
		}

		return false;
	}


	/**
	 * Enforce or Block circle's config/type
	 *
	 * @param Circle $circle
	 * @param Circle|null $previous
	 *
	 * @throws RequestBuilderException
	 */
	public function confirmAllowedCircleTypes(Circle $circle, ?Circle $previous = null): void {
		if ($this->canBypassCircleTypes($circle)) {
			return;
		}

		$config = $circle->getConfig();
		$force = $this->configService->getAppValueInt(ConfigService::CIRCLE_TYPES_FORCE);
		$block = $this->configService->getAppValueInt(ConfigService::CIRCLE_TYPES_BLOCK);

		if (is_null($previous)) {
			$config |= $force;
			$config &= ~$block;
		} else {
			// if we have a previous entry, we compare old and new config.
			foreach (array_merge($this->extractBitwise($force), $this->extractBitwise($block)) as $bit) {
				if ($previous->isConfig($bit)) {
					$config |= $bit;
				} else {
					$config &= ~$bit;
				}
			}
		}

		$circle->setConfig($config);
	}


	/**
	 * @return int[]
	 */
	private function extractBitwise(int $bitwise): array {
		$values = [];
		$b = 1;
		while ($b <= $bitwise) {
			if (($bitwise & $b) !== 0) {
				$values[] = $b;
			}

			$b = $b << 1;
		}

		return $values;
	}

	public function userMustBeMember(string $userId, string $circleId): Member {
		try {
			return $this->memberRequest->getMemberByUserId($circleId, $userId);
		} catch (MemberNotFoundException) {
			// not a direct member, check if user has inherited membership via group/circle
			try {
				$membership = $this->membershipRequest->getMembershipByUserId($circleId, $userId);
				// return group/circle member through which access is inherited, to use its permission level
				return $this->memberRequest->getMember($circleId, $membership->getInheritanceFirst());
			} catch (MembershipNotFoundException) {
				throw new InsufficientPermissionException(
					$this->l10n->t('Insufficient permissions to perform this action')
				);
			}
		}
	}

	public function memberMustBeAtLeastModerator(Member $member): void {
		$memberHelper = new MemberHelper($member);
		try {
			$memberHelper->mustBeModerator();
		} catch (MemberHelperException|MemberLevelException) {
			throw new InsufficientPermissionException(
				$this->l10n->t('Insufficient permissions to perform this action')
			);
		}
	}

	public function memberMustBeAtLeastAdmin(Member $member): void {
		$memberHelper = new MemberHelper($member);
		try {
			$memberHelper->mustBeAdmin();
		} catch (MemberHelperException|MemberLevelException) {
			throw new InsufficientPermissionException(
				$this->l10n->t('Insufficient permissions to perform this action')
			);
		}
	}

	public function memberMustBeOwner(Member $member): void {
		$memberHelper = new MemberHelper($member);
		try {
			$memberHelper->mustBeOwner();
		} catch (MemberHelperException|MemberLevelException) {
			throw new InsufficientPermissionException(
				$this->l10n->t('Insufficient permissions to perform this action')
			);
		}
	}

	public function memberMustBeHigherLevelThan(Member $memberUser, string $targetMemberId): void {
		$targetMember = $this->memberRequest->getMemberById($targetMemberId);
		$memberHelper = new MemberHelper($memberUser);
		try {
			$memberHelper->mustBeHigherLevelThan($targetMember);
		} catch (MemberLevelException) {
			throw new InsufficientPermissionException(
				$this->l10n->t('Insufficient permissions to perform this action')
			);
		}
	}
}
