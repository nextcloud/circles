<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 7/4/17
 * Time: 5:01 PM
 */

namespace OCA\Circles\Db;


use OCA\Circles\Service\TimezoneService;

class CoreRequestBuilder {

	const TABLE_FILE_SHARES = 'share';
	const SHARE_TYPE = 7;

	const TABLE_CIRCLES = 'circle_circles';
	const TABLE_MEMBERS = 'circle_members';
	const TABLE_GROUPS = 'circle_groups';
	const TABLE_SHARES = 'circle_shares';
	const TABLE_LINKS = 'circle_links';
	const TABLE_TOKENS = 'circle_tokens';
	const TABLE_GSEVENTS = 'circle_gsevents';
	const TABLE_GSSHARES = 'circle_gsshares';
	const TABLE_GSSHARES_MOUNTPOINT = 'circle_gsshares_mp';
	const TABLE_REMOTE = 'circle_remote';

	const NC_TABLE_ACCOUNTS = 'accounts';
	const NC_TABLE_GROUP_USER = 'group_user';

	/** @var array */
	private $tables = [
		self::TABLE_CIRCLES,
		self::TABLE_GROUPS,
		self::TABLE_MEMBERS,
		self::TABLE_SHARES,
		self::TABLE_LINKS,
		self::TABLE_TOKENS,
		self::TABLE_GSEVENTS,
		self::TABLE_GSSHARES,
		self::TABLE_GSSHARES_MOUNTPOINT
	];


	/** @var TimezoneService */
	protected $timezoneService;


	/**
	 * CoreRequestBuilder constructor.
	 *
	 * @param TimezoneService $timezoneService
	 */
	public function __construct(TimezoneService $timezoneService) {
		$this->timezoneService = $timezoneService;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	public function getQueryBuilder(): CoreQueryBuilder {
		return new CoreQueryBuilder();
	}

}



