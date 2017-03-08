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

use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleCreationException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailable;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class CirclesMapper extends Mapper {

	const TABLENAME = 'circles_circles';

	private $miscService;

	public function __construct(IDBConnection $db, $miscService) {
		parent::__construct($db, self::TABLENAME, 'OCA\Circles\Db\Circles');
		$this->miscService = $miscService;

	}


	/**
	 * Returns all circle from a user point-of-view
	 *
	 * @param $userId
	 * @param $type
	 * @param string $name
	 * @param int $level
	 * @param int $circleId
	 *
	 * @return Circle[]
	 * @throws ConfigNoCircleAvailable
	 */
	public function findCirclesByUser($userId, $type, $name = '', $level = 0, $circleId = -1) {

		$type = (int)$type;
		$level = (int)$level;
		$circleId = (int)$circleId;

		$qb = $this->db->getQueryBuilder();
		$qb->select(
			'c.id', 'c.name', 'c.description', 'c.type', 'c.creation',
			'u.joined', 'u.level', 'u.status'
		)
		   ->selectAlias('o.user_id', 'owner')
		   ->from(self::TABLENAME, 'c')
		   ->from(MembersMapper::TABLENAME, 'o')
		   ->where(
			   $qb->expr()
				  ->eq('c.id', 'o.circle_id'),
			   $qb->expr()
				  ->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
		   );

		$this->buildWithMemberLevel($qb, 'u.level', $level);
		$this->buildWithCircleId($qb, 'c.id', $circleId);

		$qb->leftJoin(
			'c', MembersMapper::TABLENAME, 'u',
			$qb->expr()
			   ->andX(
				   $qb->expr()
					  ->eq('c.id', 'u.circle_id'),
				   $qb->expr()
					  ->eq('u.user_id', $qb->createNamedParameter($userId))
			   )
		);

		$orTypesArray = [];
		array_push($orTypesArray, $this->generateTypeEntryForCirclePersonal($qb, $type, $userId));
		array_push(
			$orTypesArray, $this->generateTypeEntryForCircleHidden($qb, $type, $circleId, $name)
		);
		array_push($orTypesArray, $this->generateTypeEntryForCirclePrivate($qb, $type));
		array_push($orTypesArray, $this->generateTypeEntryForCirclePublic($qb, $type));

		$orTypesArray = array_filter($orTypesArray);

		if (sizeof($orTypesArray) === 0) {
			throw new ConfigNoCircleAvailable();
		}

		$orXTypes = $qb->expr()
					   ->orX();

		foreach ($orTypesArray as $orTypes) {
			$orXTypes->add($orTypes);
		}

		$qb->andWhere($orXTypes);

		$qb->groupBy('c.id');
		$qb->orderBy('c.name', 'ASC');

		$cursor = $qb->execute();

		$result = [];
		while ($data = $cursor->fetch()) {
			if ($name === '' || stripos($data['name'], $name) !== false) {
				$circle = new Circle();
				$result[] = $circle->fromArray($data);
			}
		}
		$cursor->closeCursor();

		return $result;
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param int $circleId
	 */
	private function buildWithCircleId(IQueryBuilder & $qb, string $field, int $circleId) {
		if ($circleId > 0) {
			$qb->andWhere(
				$qb->expr()
				   ->eq($field, $qb->createNamedParameter($circleId))
			);
		}
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param int $level
	 */
	private function buildWithMemberLevel(IQueryBuilder & $qb, string $field, int $level) {
		if ($level > 0) {
			$qb->andWhere(
				$qb->expr()
				   ->gte($field, $qb->createNamedParameter($level))
			);
		}
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 * @param int|string $userId
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 */
	private function generateTypeEntryForCirclePersonal(IQueryBuilder $qb, int $type, string $userId
	) {
		if (Circle::CIRCLES_PERSONAL & (int)$type) {
			return $qb->expr()
					  ->andX(
						  $qb->expr()
							 ->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL)),
						  $qb->expr()
							 ->eq('o.user_id', $qb->createNamedParameter($userId))

					  );
		}

		return null;
	}

	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 * @param int $circleId
	 * @param string $name
	 *
	 * @return string
	 */
	private function generateTypeEntryForCircleHidden(
		IQueryBuilder $qb, int $type, int $circleId, string $name
	) {

		if (!(Circle::CIRCLES_HIDDEN & (int)$type)) {
			return null;
		}

		$sqb = $qb->expr()
				  ->andX(
					  $qb->expr()
						 ->eq('c.type', $qb->createNamedParameter(Circle::CIRCLES_HIDDEN)),
					  $qb->expr()
						 ->orX(
							 $qb->expr()
								->gte(
									'u.level', $qb->createNamedParameter(Member::LEVEL_MEMBER)
								),
							 $qb->expr()
								->eq('c.id', $qb->createNamedParameter($circleId)),
							 $qb->expr()
								->eq('c.name', $qb->createNamedParameter($name))
						 )
				  );

		return $sqb;
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 *
	 * @return string
	 */
	private function generateTypeEntryForCirclePrivate(IQueryBuilder $qb, int $type) {

		if (Circle::CIRCLES_PRIVATE & (int)$type) {
			return $qb->expr()
					  ->eq(
						  'c.type',
						  $qb->createNamedParameter(Circle::CIRCLES_PRIVATE)
					  );
		}

		return null;
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $type
	 *
	 * @return string
	 */
	private function generateTypeEntryForCirclePublic(IQueryBuilder $qb, int $type) {
		if (Circle::CIRCLES_PUBLIC & (int)$type) {
			return $qb->expr()
					  ->eq(
						  'c.type',
						  $qb->createNamedParameter(Circle::CIRCLES_PUBLIC)
					  );
		}

		return null;
	}

	/**
	 * Returns details about a circle.
	 *
	 * @param string $userId
	 * @param int $circleId
	 *
	 * @return Circle
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailable
	 */
	public function getDetailsFromCircle($userId, $circleId) {

		try {
			$result = $this->findCirclesByUser($userId, Circle::CIRCLES_ALL, '', 0, $circleId);
		} catch (ConfigNoCircleAvailable $e) {
			throw $e;
		}

		if (sizeof($result) !== 1) {
			throw new CircleDoesNotExistException(
				"The circle does not exist or is hidden to the user"
			);
		}

		return $result[0];
	}


	/**
	 * @param Circle $circle
	 * @param Member $owner
	 *
	 * @return bool
	 * @throws CircleAlreadyExistsException
	 * @throws CircleCreationException
	 * @throws ConfigNoCircleAvailable
	 */
	public function create(Circle & $circle, Member & $owner) {

		if (!$this->isCircleUnique($circle, $owner)) {
			throw new CircleAlreadyExistsException();
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLENAME)
		   ->setValue('name', $qb->createNamedParameter($circle->getName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('type', $qb->createNamedParameter($circle->getType()));
		$qb->execute();
		$circleId = $qb->getLastInsertId();


		if ($circleId < 1) {
			throw new CircleCreationException();
		}

		$circle->setId($circleId);
		$owner->setLevel(Member::LEVEL_OWNER)
			  ->setStatus(Member::STATUS_MEMBER)
			  ->setCircleId($circleId);

		return true;
	}


	/**
	 * remove a circle
	 *
	 * @param Circle $circle
	 */
	public function destroy(Circle $circle) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLENAME)
		   ->where(
			   $qb->expr()
				  ->eq(
					  'id', $qb->createNamedParameter($circle->getId())
				  )
		   );

		$qb->execute();
	}


	/**
	 * returns if the circle is already in database
	 *
	 * @param Circle $circle
	 * @param Member $owner
	 *
	 * @return bool
	 */
	public function isCircleUnique(Circle $circle, Member $owner) {

		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return $this->isPersonalCircleUnique($circle, $owner);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select(
			'c.id', 'c.name', 'c.type'
		)
		   ->from(self::TABLENAME, 'c')
		   ->where(
			   $qb->expr()
				  ->neq('c.type', $qb->createNamedParameter(Circle::CIRCLES_PERSONAL))
		   );

		$cursor = $qb->execute();

		while ($data = $cursor->fetch()) {
			if (strtolower($data['name']) === strtolower($circle->getName())) {
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
	 * @param Member $owner
	 *
	 * @return bool
	 */
	private function isPersonalCircleUnique(Circle $circle, Member $owner) {

		$list = $this->findCirclesByUser(
			$owner->getUserId(), Circle::CIRCLES_PERSONAL, $circle->getName(),
			Member::LEVEL_OWNER
		);

		foreach ($list as $test) {
			if ($test->getName() === $circle->getName()) {
				return false;
			}
		}

		return true;
	}
}

