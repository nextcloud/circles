<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\User;

use Doctrine\DBAL\Platforms\OraclePlatform;
use OC\Hooks\PublicEmitter;
use OC\Memcache\WithLocalCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Support\Subscription\IAssertion;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountMappedUsersBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IGetRealUIDBackend;
use OCP\User\Backend\ILimitAwareCountUsersBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\User\Backend\ISearchKnownUsersBackend;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\User:
 * - preSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - postSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - preDelete(\OC\User\User $user)
 * - postDelete(\OC\User\User $user)
 * - preCreateUser(string $uid, string $password)
 * - postCreateUser(\OC\User\User $user, string $password)
 * - change(\OC\User\User $user)
 * - assignedUserId(string $uid)
 * - preUnassignedUserId(string $uid)
 * - postUnassignedUserId(string $uid)
 *
 * @package OC\User
 */
class Manager extends PublicEmitter implements IUserManager {
	public function __construct(private IConfig $config, ICacheFactory $cacheFactory, private IEventDispatcher $eventDispatcher, private LoggerInterface $logger)
 {
 }

	/**
	 * Get the active backends
	 * @return \OCP\UserInterface[]
	 */
	public function getBackends()
 {
 }

	/**
	 * register a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 */
	public function registerBackend($backend)
 {
 }

	/**
	 * remove a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 */
	public function removeBackend($backend)
 {
 }

	/**
	 * remove all user backends
	 */
	public function clearBackends()
 {
 }

	/**
	 * get a user by user id
	 *
	 * @param string $uid
	 * @return \OC\User\User|null Either the user or null if the specified user does not exist
	 */
	public function get($uid)
 {
 }

	public function getDisplayName(string $uid): ?string
 {
 }

	/**
	 * get or construct the user object
	 *
	 * @param string $uid
	 * @param \OCP\UserInterface $backend
	 * @param bool $cacheUser If false the newly created user object will not be cached
	 * @return \OC\User\User
	 */
	public function getUserObject($uid, $backend, $cacheUser = true)
 {
 }

	/**
	 * check if a user exists
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function userExists($uid)
 {
 }

	/**
	 * Check if the password is valid for the user
	 *
	 * @param string $loginName
	 * @param string $password
	 * @return IUser|false the User object on success, false otherwise
	 */
	public function checkPassword($loginName, $password)
 {
 }

	/**
	 * Check if the password is valid for the user
	 *
	 * @internal
	 * @param string $loginName
	 * @param string $password
	 * @return IUser|false the User object on success, false otherwise
	 */
	public function checkPasswordNoLogging($loginName, $password)
 {
 }

	/**
	 * Search by user id
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @deprecated 27.0.0, use searchDisplayName instead
	 */
	public function search($pattern, $limit = null, $offset = null)
 {
 }

	/**
	 * Search by displayName
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 */
	public function searchDisplayName($pattern, $limit = null, $offset = null)
 {
 }

	/**
	 * @return IUser[]
	 */
	public function getDisabledUsers(?int $limit = null, int $offset = 0, string $search = ''): array
 {
 }

	/**
	 * Search known users (from phonebook sync) by displayName
	 *
	 * @param string $searcher
	 * @param string $pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return IUser[]
	 */
	public function searchKnownUsersByDisplayName(string $searcher, string $pattern, ?int $limit = null, ?int $offset = null): array
 {
 }

	/**
	 * @param string $uid
	 * @param string $password
	 * @return false|IUser the created user or false
	 * @throws \InvalidArgumentException
	 * @throws HintException
	 */
	public function createUser($uid, $password)
 {
 }

	/**
	 * @param string $uid
	 * @param string $password
	 * @param UserInterface $backend
	 * @return IUser|false
	 * @throws \InvalidArgumentException
	 */
	public function createUserFromBackend($uid, $password, UserInterface $backend)
 {
 }

	/**
	 * returns how many users per backend exist (if supported by backend)
	 *
	 * @param boolean $hasLoggedIn when true only users that have a lastLogin
	 *                             entry in the preferences table will be affected
	 * @return array<string, int> an array of backend class as key and count number as value
	 */
	public function countUsers()
 {
 }

	public function countUsersTotal(int $limit = 0, bool $onlyMappedUsers = false): int|false
 {
 }

	/**
	 * returns how many users per backend exist in the requested groups (if supported by backend)
	 *
	 * @param IGroup[] $groups an array of gid to search in
	 * @return array|int an array of backend class as key and count number as value
	 *                   if $hasLoggedIn is true only an int is returned
	 */
	public function countUsersOfGroups(array $groups)
 {
 }

	/**
	 * The callback is executed for each user on each backend.
	 * If the callback returns false no further users will be retrieved.
	 *
	 * @psalm-param \Closure(\OCP\IUser):?bool $callback
	 * @param string $search
	 * @param boolean $onlySeen when true only users that have a lastLogin entry
	 *                          in the preferences table will be affected
	 * @since 9.0.0
	 */
	public function callForAllUsers(\Closure $callback, $search = '', $onlySeen = false)
 {
 }

	/**
	 * returns how many users are disabled
	 *
	 * @return int
	 * @since 12.0.0
	 */
	public function countDisabledUsers(): int
 {
 }

	/**
	 * returns how many users are disabled in the requested groups
	 *
	 * @param array $groups groupids to search
	 * @return int
	 * @since 14.0.0
	 */
	public function countDisabledUsersOfGroups(array $groups): int
 {
 }

	/**
	 * returns how many users have logged in once
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function countSeenUsers()
 {
 }

	/**
	 * @param \Closure $callback
	 * @psalm-param \Closure(\OCP\IUser):?bool $callback
	 * @since 11.0.0
	 */
	public function callForSeenUsers(\Closure $callback)
 {
 }

	/**
	 * @param string $email
	 * @return IUser[]
	 * @since 9.1.0
	 */
	public function getByEmail($email)
 {
 }

	/**
	 * @param string $uid
	 * @param bool $checkDataDirectory
	 * @throws \InvalidArgumentException Message is an already translated string with a reason why the id is not valid
	 * @since 26.0.0
	 */
	public function validateUserId(string $uid, bool $checkDataDirectory = false): void
 {
 }

	/**
	 * Gets the list of user ids sorted by lastLogin, from most recent to least recent
	 *
	 * @param int|null $limit how many users to fetch (default: 25, max: 100)
	 * @param int $offset from which offset to fetch
	 * @param string $search search users based on search params
	 * @return list<string> list of user IDs
	 */
	public function getLastLoggedInUsers(?int $limit = null, int $offset = 0, string $search = ''): array
 {
 }

	public function getDisplayNameCache(): DisplayNameCache
 {
 }
}
