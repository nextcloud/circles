<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

namespace OCA\Circles\Service;


use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCP\IL10N;
use OCP\IUserManager;

class ShareService {

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/**
	 * MembersService constructor.
	 *
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, ConfigService $configService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->miscService = $miscService;

		$this->loadSearch();
	}


	/**
	 * @param Member $member
	 */
	public function removeShareFromMember(Member $member) {



	}


}