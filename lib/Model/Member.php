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

namespace OCA\Circles\Model;

use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircle;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsBlockedException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\MemberIsOwnerException;

class Member extends BaseMember implements \JsonSerializable {


	public function inviteToCircle($circleType) {
		if ($circleType === Circle::CIRCLES_PRIVATE) {
			return $this->inviteIntoPrivateCircle();
		}

		return $this->addMemberToCircle();
	}


	/**
	 * @param int $circleType
	 *
	 * @throws MemberCantJoinCircle
	 */
	public function joinCircle($circleType) {

		switch ($circleType) {
			case Circle::CIRCLES_HIDDEN:
			case Circle::CIRCLES_PUBLIC:
				return $this->addMemberToCircle();

			case Circle::CIRCLES_PRIVATE:
				return $this->joinPrivateCircle();
		}

		throw new MemberCantJoinCircle($this->l10n->t('You cannot join this circle'));
	}


	/**
	 * Update status of member like he joined a public circle.
	 */
	private function addMemberToCircle() {

		if ($this->getStatus() === Member::STATUS_NONMEMBER
			|| $this->getStatus() === Member::STATUS_KICKED
		) {
			$this->setAsAMember(Member::LEVEL_MEMBER);
		}
	}


	/**
	 * Update status of member like he joined a private circle
	 * (invite/request)
	 */
	private function joinPrivateCircle() {

		switch ($this->getStatus()) {
			case Member::STATUS_NONMEMBER:
			case Member::STATUS_KICKED:
				$this->setStatus(Member::STATUS_REQUEST);
				break;

			case Member::STATUS_INVITED:
				$this->setAsAMember(Member::LEVEL_MEMBER);
				break;
		}
	}


	private function inviteIntoPrivateCircle() {
		switch ($this->getStatus()) {
			case Member::STATUS_NONMEMBER:
			case Member::STATUS_KICKED:
				$this->setStatus(Member::STATUS_INVITED);
				break;

			case Member::STATUS_REQUEST:
				$this->setAsAMember(Member::LEVEL_MEMBER);
				break;
		}
	}


	/**
	 * @throws MemberIsNotModeratorException
	 */
	public function hasToBeModerator() {
		if ($this->getLevel() < self::LEVEL_MODERATOR) {
			throw new MemberIsNotModeratorException(
				$this->l10n->t('This member is not a moderator')
			);
		}
	}


	/**
	 * @throws MemberIsNotModeratorException
	 */
	public function hasToBeOwner() {
		if ($this->getLevel() < self::LEVEL_OWNER) {
			throw new MemberIsNotOwnerException(
				$this->l10n->t('This member is not the owner of the circle')
			);
		}
	}


	/**
	 * @throws MemberDoesNotExistException
	 */
	public function hasToBeMember() {
		if ($this->getLevel() < self::LEVEL_MEMBER) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}
	}


	/**
	 * @throws MemberIsOwnerException
	 */
	public function cantBeOwner() {
		if ($this->getLevel() === self::LEVEL_OWNER) {
			throw new MemberIsOwnerException(
				$this->l10n->t('This member is the owner of the circle')
			);
		}
	}

	/**
	 * @throws MemberAlreadyExistsException
	 * @throws MemberIsBlockedException
	 */
	public function hasToBeAbleToJoinTheCircle() {

		if ($this->getLevel() > 0) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t("You are already a member of this circle")
			);
		}

		if ($this->getStatus() === Member::STATUS_BLOCKED) {
			throw new MemberIsBlockedException(
				$this->l10n->t("You have been blocked from this circle")
			);
		}
	}

	public function jsonSerialize() {
		return array(
			'circle_id' => $this->getCircleId(),
			'user_id' => $this->getUserId(),
			'level' => $this->getLevel(),
			'level_string' => $this->getLevelString(),
			'status' => $this->getStatus(),
			'note' => $this->getNote(),
			'joined' => $this->getJoined()
		);
	}


}


