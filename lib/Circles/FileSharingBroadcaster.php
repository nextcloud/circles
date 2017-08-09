<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


namespace OCA\Circles\Circles;

use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;


class FileSharingBroadcaster implements IBroadcaster {


	/**
	 * {@inheritdoc}
	 */
	public function init() {
	}


	/**
	 * {@inheritdoc}
	 */
	public function end() {
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToMember(SharingFrame $frame, Member $member) {
		\OC::$server->getLogger()->log(2, 'Share to Member');
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToMember(SharingFrame $frame, Member $member) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToMember(SharingFrame $frame, Member $member) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToCircle(SharingFrame $frame, Circle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToCircle(SharingFrame $frame, Circle $circle) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToCircle(SharingFrame $frame, Circle $circle) {
		return true;
	}
}