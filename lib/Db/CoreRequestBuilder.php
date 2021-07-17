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


namespace OCA\Circles\Db;

use Exception;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\TimezoneService;
use OCP\Share\IShare;

/**
 * Class CoreQueryBuilder
 *
 * @package OCA\Circles\Db
 */
class CoreRequestBuilder {
	public const TABLE_SHARE = 'share';
	public const TABLE_FILE_CACHE = 'filecache';
	public const TABLE_STORAGES = 'storages';

	public const TABLE_CIRCLE = 'circles_circle';
	public const TABLE_MEMBER = 'circles_member';
	public const TABLE_MEMBERSHIP = 'circles_membership';
	public const TABLE_REMOTE = 'circles_remote';
	public const TABLE_EVENT = 'circles_event';
	public const TABLE_MOUNT = 'circles_mount';
	public const TABLE_MOUNTPOINT = 'circles_mountpoint';

	// wip
	public const TABLE_SHARE_LOCK = 'circles_share_lock';
	public const TABLE_TOKEN = 'circles_token';

	public const TABLE_GSSHARES = 'circle_gsshares'; // rename ?
	public const TABLE_GSSHARES_MOUNTPOINT = 'circle_gsshares_mp'; // rename ?

	public const NC_TABLE_ACCOUNTS = 'accounts';
	public const NC_TABLE_GROUP_USER = 'group_user';

	/** @var array */
	public static $tables = [
		self::TABLE_CIRCLE => [
			'unique_id',
			'name',
			'display_name',
			'sanitized_name',
			'source',
			'description',
			'settings',
			'config',
			'contact_addressbook',
			'contact_groupname',
			'creation'
		],
		self::TABLE_MEMBER => [
			'circle_id',
			'member_id',
			'single_id',
			'user_id',
			'instance',
			'user_type',
			'level',
			'status',
			'note',
			'contact_id',
			'cached_name',
			'cached_update',
			'contact_meta',
			'joined'
		],
		self::TABLE_MEMBERSHIP => [
			'single_id',
			'circle_id',
			'level',
			'inheritance_first',
			'inheritance_last',
			'inheritance_path',
			'inheritance_depth'
		],
		self::TABLE_REMOTE => [
			'id',
			'type',
			'interface',
			'uid',
			'instance',
			'href',
			'item',
			'creation'
		],
		self::TABLE_EVENT => [
			'token',
			'event',
			'result',
			'instance',
			'interface',
			'severity',
			'retry',
			'status',
			'creation'
		],
		self::TABLE_MOUNT => [
			'id',
			'mount_id',
			'circle_id',
			'single_id',
			'token',
			'parent',
			'mountpoint',
			'mountpoint_hash'
		],
		self::TABLE_MOUNTPOINT => [],
		self::TABLE_SHARE_LOCK => [],
		self::TABLE_TOKEN => [
			'id',
			'share_id',
			'circle_id',
			'single_id',
			'member_id',
			'token',
			'password',
			'accepted'
		],
		self::TABLE_GSSHARES => [],
		self::TABLE_GSSHARES_MOUNTPOINT => []
	];


	public static $outsideTables = [
		self::TABLE_SHARE => [
			'id',
			'share_type',
			'share_with',
			'uid_owner',
			'uid_initiator',
			'parent',
			'item_type',
			'item_source',
			'item_target',
			'file_source',
			'file_target',
			'permissions',
			'stime',
			'accepted',
			'expiration',
			'token',
			'mail_send'
		],
		self::TABLE_FILE_CACHE => [
			'fileid',
			'path',
			'permissions',
			'storage',
			'path_hash',
			'parent',
			'name',
			'mimetype',
			'mimepart',
			'size',
			'mtime',
			'storage_mtime',
			'encrypted',
			'unencrypted_size',
			'etag',
			'checksum'
		],
		self::TABLE_STORAGES => [
			'id'
		]
	];

	/** @var TimezoneService */
	protected $timezoneService;

	/** @var ConfigService */
	protected $configService;


	/**
	 * CoreQueryBuilder constructor.
	 *
	 * @param TimezoneService $timezoneService
	 * @param ConfigService $configService
	 */
	public function __construct(TimezoneService $timezoneService, ConfigService $configService) {
		$this->timezoneService = $timezoneService;
		$this->configService = $configService;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	public function getQueryBuilder(): CoreQueryBuilder {
		return new CoreQueryBuilder();
	}


	/**
	 * @param array $ids
	 *
	 * @throws InvalidIdException
	 */
	public function confirmValidIds(array $ids): void {
		foreach ($ids as $id) {
			$this->confirmValidId($id);
		}
	}

	/**
	 * @param string $id
	 *
	 * @throws InvalidIdException
	 */
	public function confirmValidId(string $id): void {
		if (strlen($id) < 14) {
			throw new InvalidIdException();
		}
	}


	/**
	 * @param bool $shares
	 */
	public function cleanDatabase(bool $shares = false): void {
		foreach (array_keys(self::$tables) as $table) {
			$qb = $this->getQueryBuilder();
			try {
				$qb->delete($table);
				$qb->execute();
			} catch (Exception $e) {
			}
		}

		if ($shares) {
			$qb = $this->getQueryBuilder();
			$expr = $qb->expr();
			$qb->delete(self::TABLE_SHARE);
			$qb->where($expr->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE)));
			$qb->execute();
		}
	}


	public function uninstall(): void {
		$this->uninstallAppTables();
		$this->uninstallFromMigrations();
		$this->uninstallFromJobs();
		$this->configService->unsetAppConfig();
	}

	/**
	 * this just empty all tables from the app.
	 */
	public function uninstallAppTables() {
		$dbConn = \OC::$server->get(Connection::class);
		$schema = new SchemaWrapper($dbConn);

		foreach (array_keys(self::$tables) as $table) {
			if ($schema->hasTable($table)) {
				$schema->dropTable($table);
			}
		}

		$schema->performDropTableCalls();
	}


	/**
	 *
	 */
	public function uninstallFromMigrations() {
		$qb = $this->getQueryBuilder();
		$qb->delete('migrations');
		$qb->limit('app', 'circles');
		$qb->unlike('version', '001%');

		$qb->execute();
	}

	/**
	 *
	 */
	public function uninstallFromJobs() {
		$qb = $this->getQueryBuilder();
//		$qb->delete('jobs');
//		$qb->where($this->exprLimitToDBField($qb, 'class', 'OCA\Circles\', true, true));
//		$qb->execute();
	}
}
