<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCP\IL10N;

class PermissionService {
	/** @var IL10N */
	private $l10n;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ConfigService */
	private $configService;


	/**
	 * @param IL10N $l10n
	 * @param FederatedUserService $federatedUserService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n,
		FederatedUserService $federatedUserService,
		ConfigService $configService,
	) {
		$this->l10n = $l10n;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 * @throws InsufficientPermissionException
	 */
	public function confirmCircleCreation(): void {
		try {
			$this->confirm(ConfigService::LIMIT_CIRCLE_CREATION);
		} catch (InsufficientPermissionException $e) {
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
		} catch (MembershipNotFoundException $e) {
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
		} catch (MembershipNotFoundException $e) {
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
}
