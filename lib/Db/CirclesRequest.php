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


namespace OCA\Circles\Db;


use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Timezone;

class CirclesRequest extends CirclesRequestBuilder {


	/**
	 * forceGetCircle();
	 *
	 * returns data of a circle from its Id.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getCircle() instead.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return Circle
	 * @throws CircleDoesNotExistException
	 */
	public function forceGetCircle($circleUniqueId) {
		$qb = $this->getCirclesSelectSql();

		$this->limitToShortenUniqueId($qb, $circleUniqueId, Circle::SHORT_UNIQUE_ID_LENGTH);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


	/**
	 * forceGetCircles();
	 *
	 * returns data of a all circles.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getCircles() instead.
	 *
	 * @return Circle[]
	 */
	public function forceGetCircles() {

		$qb = $this->getCirclesSelectSql();
		$this->leftJoinOwner($qb);

		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circles[] = $this->parseCirclesSelectSql($data);
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
	 * @return null|Circle
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

		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


	/**
	 * @param string $userId
	 * @param int $type
	 * @param string $name
	 * @param int $level
	 *
	 * @return Circle[]
	 */
	public function getCircles($userId, $type = 0, $name = '', $level = 0) {
		if ($type === 0) {
			$type = Circle::CIRCLES_ALL;
		}

		$qb = $this->getCirclesSelectSql();
		$this->leftJoinUserIdAsViewer($qb, $userId);
		$this->leftJoinOwner($qb);
		$this->leftJoinNCGroupAndUser($qb, $userId, '`c`.`unique_id`');

		if ($level > 0) {
			$this->limitToLevel($qb, $level, ['u', 'g']);
		}
		$this->limitRegardingCircleType($qb, $userId, -1, $type, $name);

		$circles = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			if ($name === '' || stripos(strtolower($data['name']), strtolower($name)) !== false) {
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
	 *
	 * @return Circle
	 * @throws CircleDoesNotExistException
	 */
	public function getCircle($circleUniqueId, $viewerId) {
		$qb = $this->getCirclesSelectSql();

		$this->limitToShortenUniqueId($qb, $circleUniqueId, Circle::SHORT_UNIQUE_ID_LENGTH);

		$this->leftJoinUserIdAsViewer($qb, $viewerId);
		$this->leftJoinOwner($qb);
		$this->leftJoinNCGroupAndUser($qb, $viewerId, '`c`.`unique_id`');

		$this->limitRegardingCircleType($qb, $viewerId, $circleUniqueId, Circle::CIRCLES_ALL, '');

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new CircleDoesNotExistException($this->l10n->t('Circle not found'));
		}

		$circle = $this->parseCirclesSelectSql($data);
		$circle->setGroupViewer(
			$this->membersRequest->forceGetHigherLevelGroupFromUser($circleUniqueId, $viewerId)
		);

		return $circle;
	}


	/**
	 * createCircle();
	 *
	 * Create a circle with $userId as its owner.
	 * Will returns the circle
	 *
	 * @param Circle $circle
	 * @param $userId
	 *
	 * @throws CircleAlreadyExistsException
	 */
	public function createCircle(Circle &$circle, $userId) {

		if (!$this->isCircleUnique($circle, $userId)) {
			throw new CircleAlreadyExistsException(
				$this->l10n->t('A circle with that name exists')
			);
		}

		$circle->generateUniqueId();
		$qb = $this->getCirclesInsertSql();
		$qb->setValue('unique_id', $qb->createNamedParameter($circle->getUniqueId(true)))
		   ->setValue('name', $qb->createNamedParameter($circle->getName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('settings', $qb->createNamedParameter($circle->getSettings(true)))
		   ->setValue('type', $qb->createNamedParameter($circle->getType()))
		   ->setValue('creation',$qb->createNamedParameter(Timezone::getUTCTimestamp()));
		$qb->execute();

		$owner = new Member($userId, Member::TYPE_USER);
		$owner->setCircleId($circle->getUniqueId())
			  ->setLevel(Member::LEVEL_OWNER)
			  ->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($owner)
			   ->setViewer($owner);
	}


	/**
	 * remove a circle
	 *
	 * @param string $circleUniqueId
	 */
	public function destroyCircle($circleUniqueId) {
		$qb = $this->getCirclesDeleteSql($circleUniqueId);


		$qb->execute();
	}


	/**
	 * returns if the circle is already in database
	 *
	 * @param Circle $circle
	 * @param string $userId
	 *
	 * @return bool
	 */
	private function isCircleUnique(Circle $circle, $userId) {

		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return $this->isPersonalCircleUnique($circle, $userId);
		}

		$qb = $this->getCirclesSelectSql();
		$this->limitToNonPersonalCircle($qb);

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			if (strtolower($data['name']) === strtolower($circle->getName())
				&& $circle->getUniqueId() !== $data['unique_id']) {
				return false;
			}
		}
		$cursor->closeCursor();

		return true;
	}


	/**
	 * return if the personal circle is unique
	 *
	 * @param Circle $circle
	 * @param string $userId
	 *
	 * @return bool
	 */
	private function isPersonalCircleUnique(Circle $circle, $userId) {

		$list = $this->getCircles(
			$userId, Circle::CIRCLES_PERSONAL, $circle->getName(),
			Member::LEVEL_OWNER
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
	 * @param Circle $circle
	 * @param string $userId
	 *
	 * @throws CircleAlreadyExistsException
	 */
	public function updateCircle(Circle $circle, $userId) {

		if (!$this->isCircleUnique($circle, $userId)) {
			throw new CircleAlreadyExistsException(
				$this->l10n->t('A circle with that name exists')
			);
		}

		$qb = $this->getCirclesUpdateSql($circle->getUniqueId(true));
		$qb->set('name', $qb->createNamedParameter($circle->getName()))
		   ->set('description', $qb->createNamedParameter($circle->getDescription()))
		   ->set('settings', $qb->createNamedParameter($circle->getSettings(true)));

		$qb->execute();
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return Circle
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

		$entry = $this->parseCirclesSelectSql($data);

		return $entry;
	}


}