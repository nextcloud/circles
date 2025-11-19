<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsBlockedException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Exceptions\MemberTypeCantEditLevelException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;

class DeprecatedMember extends BaseMember {
	public function inviteToCircle($circleType) {
		if ($circleType === 0) {
			throw new CircleTypeNotValidException('Invalid circle type');
		}

		if ($circleType === DeprecatedCircle::CIRCLES_CLOSED) {
			return $this->inviteIntoClosedCircle();
		}

		return $this->addMemberToCircle();
	}


	/**
	 * @param int $circleType
	 *
	 * @throws MemberCantJoinCircleException
	 */
	public function joinCircle($circleType) {
		switch ($circleType) {
			case DeprecatedCircle::CIRCLES_SECRET:
			case DeprecatedCircle::CIRCLES_PUBLIC:
				return $this->addMemberToCircle();

			case DeprecatedCircle::CIRCLES_CLOSED:
				return $this->joinClosedCircle();
		}

		throw new MemberCantJoinCircleException($this->l10n->t('You cannot join this team'));
	}


	/**
	 * Update status of member like he joined a public circle.
	 */
	public function addMemberToCircle() {
		if ($this->getStatus() === DeprecatedMember::STATUS_NONMEMBER
			|| $this->getStatus() === DeprecatedMember::STATUS_KICKED
		) {
			$this->setAsAMember(DeprecatedMember::LEVEL_MEMBER);
		}
	}


	/**
	 * Update status of member like he joined a closed circle
	 * (invite/request)
	 */
	private function joinClosedCircle() {
		switch ($this->getStatus()) {
			case DeprecatedMember::STATUS_NONMEMBER:
			case DeprecatedMember::STATUS_KICKED:
				$this->setStatus(DeprecatedMember::STATUS_REQUEST);
				break;

			case DeprecatedMember::STATUS_INVITED:
				$this->setAsAMember(DeprecatedMember::LEVEL_MEMBER);
				break;
		}
	}


	private function inviteIntoClosedCircle() {
		switch ($this->getStatus()) {
			case DeprecatedMember::STATUS_NONMEMBER:
			case DeprecatedMember::STATUS_KICKED:
				$this->setStatus(DeprecatedMember::STATUS_INVITED);
				break;

			case DeprecatedMember::STATUS_REQUEST:
				$this->setAsAMember(DeprecatedMember::LEVEL_MEMBER);
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
	 * @param $level
	 *
	 * @throws ModeratorIsNotHighEnoughException
	 */
	public function hasToBeHigherLevel($level) {
		if ($this->getLevel() <= $level) {
			throw new ModeratorIsNotHighEnoughException(
				$this->l10n->t('Insufficient privileges')
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

		return true;
	}


	/**
	 * @throws MemberDoesNotExistException
	 */
	public function hasToBeMemberOrAlmost() {
		if ($this->isAlmostMember() || $this->hasToBeMember()) {
			return true;
		}

		throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
	}


	/**
	 * @throws MemberIsOwnerException
	 */
	public function cantBeOwner() {
		if ($this->getLevel() === self::LEVEL_OWNER) {
			throw new MemberIsOwnerException(
				$this->l10n->t('This member is the owner of the team')
			);
		}
	}


	/**
	 * return if member already exists
	 *
	 * @return bool
	 */
	public function alreadyExistOrJoining() {
		return ($this->getLevel() > DeprecatedMember::LEVEL_NONE
				|| ($this->getStatus() !== DeprecatedMember::STATUS_NONMEMBER
					&& $this->getStatus() !== DeprecatedMember::STATUS_REQUEST)
		);
	}


	/**
	 * @param bool $able
	 */
	public function broadcasting($able) {
		$this->broadcasting = $able;
	}


	/**
	 * @return bool
	 */
	public function isBroadcasting() {
		return $this->broadcasting;
	}


	/**
	 * @throws MemberTypeCantEditLevelException
	 */
	public function levelHasToBeEditable() {
		if ($this->getType() !== self::TYPE_USER) {
			throw new MemberTypeCantEditLevelException(
				$this->l10n->t('Level cannot be changed for this type of member')
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
				$this->l10n->t('You are already a member of this team')
			);
		}

		if ($this->getStatus() === DeprecatedMember::STATUS_BLOCKED) {
			throw new MemberIsBlockedException(
				$this->l10n->t('You have been blocked from this team')
			);
		}
	}


	/**
	 * @throws MemberAlreadyExistsException
	 */
	public function hasToBeInviteAble() {
		if ($this->getLevel() > 0) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('The account is already a member of this team')
			);
		}

		if ($this->getStatus() === DeprecatedMember::STATUS_INVITED) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('The account has already been invited into this team')
			);
		}
	}
}
