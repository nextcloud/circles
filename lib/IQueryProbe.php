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


namespace OCA\Circles;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;

/**
 * Interface IQueryProbe
 *
 * @package OCA\Circles
 */
interface IQueryProbe {
	/**
	 * @return int
	 */
	public function getItemsOffset(): int;

	/**
	 * @return int
	 */
	public function getItemsLimit(): int;

	/**
	 * @return int
	 */
	public function getDetails(): int;

	/**
	 * @param int $detail
	 *
	 * @return bool
	 */
	public function showDetail(int $detail): bool;

	/**
	 * @return Circle
	 */
	public function getFilterCircle(): Circle;

	/**
	 * @return bool
	 */
	public function hasFilterCircle(): bool;

	/**
	 * @return Member
	 */
	public function getFilterMember(): Member;

	/**
	 * @return bool
	 */
	public function hasFilterMember(): bool;

	/**
	 * @return RemoteInstance
	 */
	public function getFilterRemoteInstance(): RemoteInstance;

	/**
	 * @return bool
	 */
	public function hasFilterRemoteInstance(): bool;

	/**
	 * @return array
	 */
	public function getAsOptions(): array;
}
