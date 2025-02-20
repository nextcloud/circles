<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IAttributes;
use OCP\Share\IShare;

class Share implements IShare {
	public function __construct(
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function setId($id)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getId()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getFullId()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setProviderId($id)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNode(Node $node)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNode()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNodeId($fileId)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNodeId(): int
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNodeType($type)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNodeType()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setShareType($shareType)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getShareType()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setSharedWith($sharedWith)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getSharedWith()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setSharedWithDisplayName($displayName)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getSharedWithDisplayName()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setSharedWithAvatar($src)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getSharedWithAvatar()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setPermissions($permissions)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getPermissions()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function newAttributes(): IAttributes
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setAttributes(?IAttributes $attributes)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getAttributes(): ?IAttributes
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setStatus(int $status): IShare
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getStatus(): int
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNote($note)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNote()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setLabel($label)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getLabel()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setExpirationDate($expireDate)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getExpirationDate()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNoExpirationDate(bool $noExpirationDate)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNoExpirationDate(): bool
 {
 }

	/**
	 * @inheritdoc
	 */
	public function isExpired()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setSharedBy($sharedBy)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getSharedBy()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setShareOwner($shareOwner)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getShareOwner()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setPassword($password)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getPassword()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setPasswordExpirationTime(?\DateTimeInterface $passwordExpirationTime = null): IShare
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getPasswordExpirationTime(): ?\DateTimeInterface
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setSendPasswordByTalk(bool $sendPasswordByTalk)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getSendPasswordByTalk(): bool
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setToken($token)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getToken()
 {
 }

	/**
	 * Set the parent of this share
	 *
	 * @param int $parent
	 * @return IShare
	 * @deprecated 12.0.0 The new shares do not have parents. This is just here for legacy reasons.
	 */
	public function setParent($parent)
 {
 }

	/**
	 * Get the parent of this share.
	 *
	 * @return int
	 * @deprecated 12.0.0 The new shares do not have parents. This is just here for legacy reasons.
	 */
	public function getParent()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setTarget($target)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getTarget()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setShareTime(\DateTime $shareTime)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getShareTime()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setMailSend($mailSend)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getMailSend()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setNodeCacheEntry(ICacheEntry $entry)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getNodeCacheEntry()
 {
 }

	public function setHideDownload(bool $hide): IShare
 {
 }

	public function getHideDownload(): bool
 {
 }

	public function setReminderSent(bool $reminderSent): IShare
 {
 }

	public function getReminderSent(): bool
 {
 }
}
