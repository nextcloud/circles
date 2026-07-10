<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OC\Hooks\PublicEmitter;
use OC\User\LazyUser;
use OC\User\User;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\Group\Events\BeforeGroupChangedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\BeforeUserAddedEvent;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\GroupInterface;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

class Group implements IGroup {
	public function __construct(
		private string $gid,
		/** @var list<GroupInterface> */
		private array $backends,
		private IEventDispatcher $dispatcher,
		private IUserManager $userManager,
		private ?PublicEmitter $emitter = null,
		protected ?string $displayName = null,
	) {
	}

	public function getGID(): string
    {
    }

	public function getDisplayName(): string
    {
    }

	public function setDisplayName(string $displayName): bool
    {
    }

	/**
	 * get all users in the group
	 *
	 * @return array<string, IUser>
	 */
	public function getUsers(): array
    {
    }

	/**
	 * check if a user is in the group
	 *
	 * @param IUser $user
	 * @return bool
	 */
	public function inGroup(IUser $user): bool
    {
    }

	/**
	 * add a user to the group
	 *
	 * @param IUser $user
	 */
	public function addUser(IUser $user): void
    {
    }

	/**
	 * remove a user from the group
	 */
	public function removeUser(IUser $user): void
    {
    }

	/**
	 * Search for users in the group by userid or display name
	 * @return IUser[]
	 */
	public function searchUsers(string $search, ?int $limit = null, ?int $offset = null): array
    {
    }

	/**
	 * returns the number of users matching the search string
	 *
	 * @param string $search
	 * @return int|bool
	 */
	public function count($search = ''): int|bool
    {
    }

	/**
	 * returns the number of disabled users
	 *
	 * @return int|bool
	 */
	public function countDisabled(): int|bool
    {
    }

	/**
	 * search for users in the group by displayname
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @deprecated 27.0.0 Use searchUsers instead (same implementation)
	 */
	public function searchDisplayName(string $search, ?int $limit = null, ?int $offset = null): array
    {
    }

	/**
	 * Get the names of the backend classes the group is connected to
	 *
	 * @return string[]
	 */
	public function getBackendNames(): array
    {
    }

	/**
	 * Delete the group
	 *
	 * @return bool
	 */
	public function delete(): bool
    {
    }

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canRemoveUser(): bool
    {
    }

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canAddUser(): bool
    {
    }

	/**
	 * @return bool
	 * @since 16.0.0
	 */
	public function hideFromCollaboration(): bool
    {
    }
}
