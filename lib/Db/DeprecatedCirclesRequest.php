<?php

declare(strict_types=1);


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


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;

class DeprecatedCirclesRequest extends DeprecatedCirclesRequestBuilder {
	/**
	 * forceGetCircle();
	 *
	 * returns data of a circle from its Id.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getCircle() instead.
	 *
	 * @param string $circleUniqueId
	 * @param bool $allSettings
	 *
	 * @return DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 */
	public function forceGetCircle($circleUniqueId, bool $allSettings = false) {
		$qb = $this->getCirclesSelectSql();

		$this->leftJoinOwner($qb, '');
		$this->limitToUniqueId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		return $this->parseCirclesSelectSql($data, $allSettings);
	}


	/**
	 * forceGetCircles();
	 *
	 * returns data of a all circles.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getCircles() instead.
	 *
	 * @param string $ownerId
	 *
	 * @return DeprecatedCircle[]
	 */
	public function forceGetCircles(string $ownerId = '') {
		$qb = $this->getCirclesSelectSql();
		$this->leftJoinOwner($qb, $ownerId);

		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circles[] = $this->parseCirclesSelectSql($data, true);
		}
		$cursor->closeCursor();

		return $circles;
	}


	/**
	 * forceGetCircleByName();
	 *
	 * returns data of a circle from its Name.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, do not use this method.
	 *
	 * @param $name
	 *
	 * @return null|DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 */
	public function forceGetCircleByName($name) {
		$qb = $this->getCirclesSelectSql();

		$this->limitToName($qb, $name);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		return $this->parseCirclesSelectSql($data);
	}


	/**
	 * @param string $userId
	 * @param int $circleType
	 * @param string $name
	 * @param int $level
	 * @param bool $forceAll
	 * @param string $ownerId
	 *
	 * @return DeprecatedCircle[]
	 * @throws ConfigNoCircleAvailableException
	 * @throws GSStatusException
	 */
	public function getCircles(
		string $userId, int $circleType = 0, string $name = '', int $level = 0, bool $forceAll = false,
		string $ownerId = ''
	) {
		if ($circleType === 0) {
			$circleType = DeprecatedCircle::CIRCLES_ALL;
		}

		// todo - make it works based on $type
		$typeViewer = DeprecatedMember::TYPE_USER;

		$qb = $this->getCirclesSelectSql();
		$this->leftJoinUserIdAsViewer($qb, $userId, $typeViewer, '');
		$this->leftJoinOwner($qb, $ownerId);
		$this->leftJoinNCGroupAndUser($qb, $userId, 'c.unique_id');

		if ($level > 0) {
			$this->limitToLevel($qb, $level, ['u', 'g']);
		}
		$this->limitRegardingCircleType($qb, $userId, -1, $circleType, $name, $forceAll);

		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			if ($name === '' || stripos(strtolower($data['name']), strtolower($name)) !== false
				|| stripos(strtolower($data['alt_name']), strtolower($name)) !== false) {
				$circles[] = $this->parseCirclesSelectSql($data);
			}
		}
		$cursor->closeCursor();

		return $circles;
	}


	/**
	 *
	 * @param string $circleUniqueId
	 * @param string $viewerId
	 * @param int $type
	 * @param string $instanceId
	 * @param bool $forceAll
	 *
	 * @return DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 */
	public function getCircle(
		string $circleUniqueId, string $viewerId, int $type = DeprecatedMember::TYPE_USER,
		string $instanceId = '',
		bool $forceAll = false
	) {
		$qb = $this->getCirclesSelectSql();

		$this->limitToUniqueId($qb, $circleUniqueId);

		$this->leftJoinUserIdAsViewer($qb, $viewerId, $type, $instanceId);
		$this->leftJoinOwner($qb);
		if ($instanceId === '') {
			$this->leftJoinNCGroupAndUser($qb, $viewerId, 'c.unique_id');
		}

		$this->limitRegardingCircleType(
			$qb, $viewerId, $circleUniqueId, DeprecatedCircle::CIRCLES_ALL, '', $forceAll
		);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found ' . $circleUniqueId));
		}

		$circle = $this->parseCirclesSelectSql($data);
		if ($instanceId === '') {
			$circle->setGroupViewer(
				$this->membersRequest->forceGetHigherLevelGroupFromUser($circleUniqueId, $viewerId)
			);
		}

		return $circle;
	}


	/**
	 * createCircle();
	 *
	 * Create a circle with $userId as its owner.
	 * Will returns the circle
	 *
	 * @param DeprecatedCircle $circle
	 */
	public function createCircle(DeprecatedCircle $circle) {
		$config = DeprecatedCircle::convertTypeToConfig($circle->getType());

		$qb = $this->getCirclesInsertSql();
		$qb->setValue('unique_id', $qb->createNamedParameter($circle->getUniqueId()))
		   ->setValue('long_id', $qb->createNamedParameter($circle->getUniqueId(true)))
		   ->setValue('name', $qb->createNamedParameter($circle->getName(true)))
		   ->setValue('alt_name', $qb->createNamedParameter($circle->getAltName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('contact_addressbook', $qb->createNamedParameter($circle->getContactAddressBook()))
		   ->setValue('contact_groupname', $qb->createNamedParameter($circle->getContactGroupName()))
		   ->setValue('settings', $qb->createNamedParameter($circle->getSettings(true)))
		   ->setValue('type', $qb->createNamedParameter($circle->getType()))
		   ->setValue('config', $qb->createNamedParameter($config));
		$qb->execute();
	}


	/**
	 * remove a circle
	 *
	 * @param string $circleUniqueId
	 */
	public function destroyCircle($circleUniqueId) {
	}


	/**
	 * returns if the circle is already in database
	 *
	 * @param DeprecatedCircle $circle
	 * @param string $userId
	 *
	 * @return bool
	 * @throws ConfigNoCircleAvailableException
	 */
	public function isCircleUnique(DeprecatedCircle $circle, $userId = '') {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return $this->isPersonalCircleUnique($circle, $userId);
		}

		$qb = $this->getCirclesSelectSql();
		$this->limitToNonPersonalCircle($qb);

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			if (strtolower($data['name']) === strtolower($circle->getName())
				&& $circle->getUniqueId(true) !== $data['unique_id']) {
				return false;
			}
		}
		$cursor->closeCursor();

		return true;
	}


	/**
	 * return if the personal circle is unique
	 *
	 * @param DeprecatedCircle $circle
	 * @param string $userId
	 *
	 * @return bool
	 * @throws ConfigNoCircleAvailableException
	 */
	private function isPersonalCircleUnique(DeprecatedCircle $circle, $userId = '') {
		if ($userId === '') {
			return true;
		}

		$list = $this->getCircles(
			$userId, DeprecatedCircle::CIRCLES_PERSONAL, $circle->getName(),
			DeprecatedMember::LEVEL_OWNER
		);

		foreach ($list as $test) {
			if (strtolower($test->getName()) === strtolower($circle->getName())
				&& $circle->getUniqueId(true) !== $test->getUniqueId(true)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 */
	public function getCircleFromUniqueId($uniqueId) {
		$qb = $this->getCirclesSelectSql();
		$this->limitToUniqueId($qb, (string)$uniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		return $this->parseCirclesSelectSql($data);
	}


	/**
	 * @param int $addressBookId
	 *
	 * @return array
	 */
	public function getFromBook(int $addressBookId) {
		$qb = $this->getCirclesSelectSql();
		$this->limitToAddressBookId($qb, $addressBookId);

		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circles[] = $this->parseCirclesSelectSql($data);
		}
		$cursor->closeCursor();

		return $circles;
	}


	/**
	 * @param int $addressBookId
	 *
	 * @return DeprecatedCircle[]
	 */
	public function getFromContactBook(int $addressBookId): array {
		$qb = $this->getCirclesSelectSql();

		if ($addressBookId > 0) {
			$this->limitToAddressBookId($qb, $addressBookId);
		}


		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circles[] = $this->parseCirclesSelectSql($data);
		}
		$cursor->closeCursor();

		return $circles;
	}


	/**
	 * @param int $addressBookId
	 * @param string $group
	 *
	 * @return DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 */
	public function getFromContactGroup(int $addressBookId, string $group): DeprecatedCircle {
		$qb = $this->getCirclesSelectSql();
		$this->limitToAddressBookId($qb, $addressBookId);
		$this->limitToContactGroup($qb, $group);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		return $this->parseCirclesSelectSql($data);
	}
}
