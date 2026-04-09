<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use InvalidArgumentException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\CircleEdit;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IL10N;
use OCP\Security\ISecureRandom;

class AvatarService {
	public function __construct(
		private IAppData $appData,
		private IL10N $l,
		private ISecureRandom $random,
		private CircleService $circleService,
		private FederatedEventService $federatedEventService,
	) {
	}

	/**
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 * @throws \RuntimeException
	 */
	public function getAvatar(string $circleId): ?ISimpleFile {
		$circle = $this->circleService->getCircle($circleId);
		$fileName = $circle->getAvatar();

		if ($fileName === '') {
			return null;
		}

		try {
			$folder = $this->appData->getFolder('circle-avatar');
			if ($folder->fileExists($circleId)) {
				return $folder->getFolder($circleId)->getFile($fileName);
			}
		} catch (NotFoundException $e) {
		}

		return null;
	}

	/**
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidArgumentException
	 * @throws NotPermittedException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 */
	public function updateAvatar(string $circleId, ?array $file): array {
		$circle = $this->circleService->getCircle($circleId);

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$avatarFileName = $this->setAvatar($circleId, $file);

		$event = new FederatedEvent(CircleEdit::class);
		$event->setCircle($circle);
		$event->setParams(new SimpleDataStore(['avatar' => $avatarFileName]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

	/**
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 * @throws NotPermittedException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 */
	public function removeAvatar(string $circleId): array {
		$circle = $this->circleService->getCircle($circleId);

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$this->deleteAvatar($circleId);

		$event = new FederatedEvent(CircleEdit::class);
		$event->setCircle($circle);
		$event->setParams(new SimpleDataStore(['avatar' => '']));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

	private function setAvatar(string $circleId, ?array $file): string {
		if ($file === null) {
			throw new InvalidArgumentException($this->l->t('No image file provided'));
		}

		if ($file['error'] !== 0 || !is_uploaded_file($file['tmp_name'])) {
			throw new InvalidArgumentException($this->l->t('Invalid file provided'));
		}
		if ($file['size'] > 20 * 1024 * 1024) {
			throw new InvalidArgumentException($this->l->t('File is too big'));
		}

		$content = file_get_contents($file['tmp_name']);
		unlink($file['tmp_name']);
		$image = new \OCP\Image();
		$image->loadFromData($content);
		$image->readExif($content);

		$image->fixOrientation();

		if (!$image->valid()) {
			throw new InvalidArgumentException($this->l->t('Invalid image'));
		}

		$mimeType = $image->mimeType();
		$allowedMimeTypes = [
			'image/jpeg',
			'image/png',
		];
		if (!in_array($mimeType, $allowedMimeTypes)) {
			throw new InvalidArgumentException($this->l->t('Unknown filetype'));
		}

		$avatarFolder = $this->getAvatarFolder($circleId);

		// Delete previous avatars
		foreach ($avatarFolder->getDirectoryListing() as $file) {
			$file->delete();
		}

		$avatarFileName = $this->random->generate(16, ISecureRandom::CHAR_HUMAN_READABLE);
		if ($mimeType === 'image/jpeg') {
			$avatarFileName .= '.jpg';
		} else {
			$avatarFileName .= '.png';
		}

		$avatarFolder->newFile($avatarFileName, $image->data());

		return $avatarFileName;
	}

	private function deleteAvatar(string $circleId): void {
		$avatarFolder = $this->getAvatarFolder($circleId);

		foreach ($avatarFolder->getDirectoryListing() as $file) {
			$file->delete();
		}
	}

	private function getAvatarFolder(string $circleId): ISimpleFolder {
		try {
			$folder = $this->appData->getFolder('circle-avatar');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('circle-avatar');
		}
		try {
			$avatarFolder = $folder->getFolder($circleId);
		} catch (NotFoundException $e) {
			$avatarFolder = $folder->newFolder($circleId);
		}
		return $avatarFolder;
	}
}
