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


use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\TimezoneService;


/**
 * Class CoreRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class CoreRequestBuilder {

	const TABLE_FILE_SHARES = 'share';
	const SHARE_TYPE = 7;

	const TABLE_CIRCLE = 'circle_circles';
	const TABLE_MEMBER = 'circle_members';
	const TABLE_GROUPS = 'circle_groups';
	const TABLE_SHARES = 'circle_shares';
	const TABLE_LINKS = 'circle_links';
	const TABLE_TOKENS = 'circle_tokens';
	const TABLE_GSEVENTS = 'circle_gsevents';
	const TABLE_GSSHARES = 'circle_gsshares';
	const TABLE_GSSHARES_MOUNTPOINT = 'circle_gsshares_mp';
	const TABLE_REMOTE = 'circle_remotes';

	const NC_TABLE_ACCOUNTS = 'accounts';
	const NC_TABLE_GROUP_USER = 'group_user';

	/** @var array */
	private $tables = [
		self::TABLE_CIRCLE,
		self::TABLE_GROUPS,
		self::TABLE_MEMBER,
		self::TABLE_SHARES,
		self::TABLE_LINKS,
		self::TABLE_TOKENS,
		self::TABLE_GSEVENTS,
		self::TABLE_GSSHARES,
		self::TABLE_GSSHARES_MOUNTPOINT,
		self::TABLE_REMOTE
	];


	/** @var TimezoneService */
	protected $timezoneService;

	/** @var ConfigService */
	protected $configService;


	/**
	 * CoreRequestBuilder constructor.
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

}



