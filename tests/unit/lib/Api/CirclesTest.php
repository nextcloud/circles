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

namespace OCA\Circles\Tests\Api;

use Exception;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Tests\Env;
use OCP\AppFramework\QueryException;

class CirclesTest extends \PHPUnit_Framework_TestCase {
	public const NAME_PUBLIC_CIRCLE1 = '_circleNamePublic1';
	public const NAME_SECRET_CIRCLE1 = '_circleNameSecret1';
	public const NAME_CLOSED_CIRCLE1 = '_circleNameClosed1';
	public const NAME_PERSONAL_CIRCLE1 = '_circleNamePersonal1';

	public const NAME_PUBLIC_CIRCLE2 = '_circleNamePublic2';
	public const NAME_SECRET_CIRCLE2 = '_circleNameSecret2';
	public const NAME_CLOSED_CIRCLE2 = '_circleNameClosed2';
	public const NAME_PERSONAL_CIRCLE2 = '_circleNamePersonal2';


	/** @var DeprecatedCircle[] */
	private $circles;

	/**
	 * setUp() is initiated before each test.
	 *
	 * Function will create 4 differents circles under user ENV_TEST_OWNER1
	 *
	 * @throws Exception
	 */
	protected function setUp() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$this->circles = [];
		try {
			$this->circles = [
				'Public' =>
					Circles::createCircle(DeprecatedCircle::CIRCLES_PUBLIC, self::NAME_PUBLIC_CIRCLE1),
				'Secret' =>
					Circles::createCircle(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE1),
				'Closed' =>
					Circles::createCircle(DeprecatedCircle::CIRCLES_CLOSED, self::NAME_CLOSED_CIRCLE1),
				'Personal' =>
					Circles::createCircle(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1)
			];
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();
	}


	/**
	 * tearDown() is initiated after each test.
	 *
	 * Function will destroy the circles created in setUp()
	 *
	 * @throws Exception
	 */
	protected function tearDown() {
		Env::setUser(Env::ENV_TEST_OWNER1);
		try {
			foreach ($this->circles as $circle) {
				Circles::destroyCircle($circle->getId());
			}
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();
	}


	/**
	 * Testing Circles::version()
	 */
	public function testVersion() {
		$this->assertSame(Circles::version(), Circles::API_VERSION);
	}


	/**
	 * Testing the tools to switch users
	 */
	public function testUserSession() {
		Env::setUser(Env::ENV_TEST_ADMIN1);
		$this->assertEquals(Env::currentUser(), Env::ENV_TEST_ADMIN1);
		Env::setUser(Env::ENV_TEST_OWNER3);
		try {
			$this->assertEquals(Env::currentUser(), Env::ENV_TEST_ADMIN1);
			$this->assertSame(true, false, 'should return an exception');
		} catch (Exception $e) {
		}
		Env::setUser(Env::ENV_TEST_OWNER1);
		$this->assertEquals(Env::currentUser(), Env::ENV_TEST_OWNER1);
	}

	/**
	 * Testing Leveling Members. (not in Personal Circle)
	 *
	 * @throws Exception
	 */
	public function testLevelMemberInCircles() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$circles = [$this->circles['Public'], $this->circles['Closed'], $this->circles['Secret']];

		// OWNER1 Should be able to add/level anyone to Admin Level at least
		try {
			foreach ($circles as $circle) {
				$this->generateSimpleCircleWithAllLevel(
					$circle->getId(), ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED)
				);
			}
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();


		// ADMIN1 should be able to add/level anyone to Moderator level
		Env::setUser(Env::ENV_TEST_ADMIN1);

		try {
			foreach ($circles as $circle) {
				Circles::addMember($circle->getId(), Env::ENV_TEST_ADMIN2, DeprecatedMember::TYPE_USER);

				if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED) {
					// In closed circle, we need to confirm the invitation
					Env::setUser(Env::ENV_TEST_ADMIN2);
					Circles::joinCircle($circle->getId());
					Env::setUser(Env::ENV_TEST_ADMIN1);
				}

				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_ADMIN2, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_MODERATOR
				);
			}
		} catch (Exception $e) {
			throw $e;
		}
		Env::logout();


		// ADMIN1 should not be able to level anyone to Admin Level
		Env::setUser(Env::ENV_TEST_ADMIN1);

		foreach ($circles as $circle) {
			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_ADMIN3, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_MODERATOR
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberDoesNotExistException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a MemberDoesNotExistException'
				);
			}

			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_ADMIN2, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_ADMIN
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (ModeratorIsNotHighEnoughException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a ModeratorIsNotHighEnoughException'
				);
			}

			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_ADMIN2, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_OWNER
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberIsNotOwnerException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a MemberIsNotOwnerException'
				);
			}
		}

		Env::logout();


		// MODERATOR1 should be able to add anyone
		Env::setUser(Env::ENV_TEST_MODERATOR1);

		try {
			foreach ($circles as $circle) {
				Circles::addMember($circle->getId(), Env::ENV_TEST_MODERATOR2, DeprecatedMember::TYPE_USER);
				if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED) {
					// In closed circle, we need to confirm the invitation
					Env::setUser(Env::ENV_TEST_MODERATOR2);
					Circles::joinCircle($circle->getId());
					Env::setUser(Env::ENV_TEST_MODERATOR1);
				}
			}
		} catch (Exception $e) {
			throw $e;
		}

		Env::logout();


		// MODERATOR1 should not be able to add/level anyone to Moderator/Admin Level
		Env::setUser(Env::ENV_TEST_MODERATOR1);

		foreach ($circles as $circle) {
			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_MODERATOR2, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_MODERATOR
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (ModeratorIsNotHighEnoughException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a ModeratorIsNotHighEnoughException'
				);
			}
			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_MODERATOR2, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_ADMIN
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (ModeratorIsNotHighEnoughException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a ModeratorIsNotHighEnoughException'
				);
			}
			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_MODERATOR2, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_OWNER
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberIsNotOwnerException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a MemberIsNotOwnerException'
				);
			}
		}

		Env::logout();


		// MEMBER1 should not be able to add/level anyone to any level
		Env::setUser(Env::ENV_TEST_MEMBER1);

		foreach ($circles as $circle) {
			try {
				Circles::addMember(
					$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberIsNotModeratorException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a MemberIsNotModeratorException'
				);
			}

			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_USER1, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_MEMBER
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberDoesNotExistException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false, 'should have returned a MemberDoesNotExistException'
				);
			}

			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_MEMBER1, DeprecatedMember::TYPE_USER,
					DeprecatedMember::LEVEL_MODERATOR
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberIsNotModeratorException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false,
					'should have returned a MemberIsNotModeratorException - ' . $e->getMessage()
				);
			}


			try {
				Circles::levelMember(
					$circle->getId(), Env::ENV_TEST_OWNER1, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_MEMBER
				);
				$this->assertSame(true, false, 'should return an exception');
			} catch (MemberIsNotModeratorException $e) {
			} catch (Exception $e) {
				$this->assertSame(
					true, false,
					'should have returned a MemberIsNotModeratorException - ' . $e->getMessage()
				);
			}
		}

		Env::logout();
	}


	/**
	 * Testing Leveling Members in Personal Circle.
	 *
	 * @throws Exception
	 */
	public function testLevelMemberInPersonalCircle() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		try {
			$this->generateSimpleCircleWithAllLevel($this->circles['Personal']->getId());
			$this->assertSame(true, false, 'should return an exception');
		} catch (CircleTypeNotValidException $e) {
		} catch (Exception $e) {
			$this->assertSame(true, false, 'should have returned a CircleTypeNotValid');
		}

		Env::logout();
	}


	/**
	 * Testing creation of a circle with duplicate name as the owner.
	 */
	public function testCreateCircleWithDuplicate() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		$circleNames = [
			self::NAME_PUBLIC_CIRCLE1,
			self::NAME_SECRET_CIRCLE1,
			self::NAME_CLOSED_CIRCLE1
		];

		for ($i = 0; $i < sizeof(Env::listCircleTypes()); $i++) {
			if (Env::listCircleTypes()[$i] === DeprecatedCircle::CIRCLES_PERSONAL) {
				try {
					Circles::createCircle(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1);
					$this->assertSame(true, false, 'should return an exception');
				} catch (CircleAlreadyExistsException $e) {
				} catch (Exception $e) {
					$this->assertSame(
						true, false, 'should have returned a CircleAlreadyExistsException'
					);
				}
			} else {
				for ($j = 0; $j < sizeof($circleNames); $j++) {
					try {
						Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
						$this->assertSame(true, false, 'should return an exception');
					} catch (CircleAlreadyExistsException $e) {
					} catch (Exception $e) {
						$this->assertSame(
							true, false, 'should have returned a CircleAlreadyExistsException'
						);
					}
				}
			}
		}

		Env::logout();
	}


	/**
	 * Testing creation of a circle with duplicate name as a new owner.
	 */
	public function testCreateCircleWithDuplicateFromOthers() {
		Env::setUser(Env::ENV_TEST_OWNER2);

		$circleNames = [
			self::NAME_PUBLIC_CIRCLE1,
			self::NAME_SECRET_CIRCLE1,
			self::NAME_CLOSED_CIRCLE1,
		];

		$circles = [];
		array_push(
			$circles, Circles::createCircle(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1)
		);

		for ($i = 0; $i < sizeof(Env::listCircleTypes()); $i++) {
			for ($j = 0; $j < sizeof($circleNames); $j++) {
				if (Env::listCircleTypes()[$i] === DeprecatedCircle::CIRCLES_PERSONAL) {
					try {
						array_push(
							$circles, Circles::createCircle(
							Env::listCircleTypes()[$i], $circleNames[$j]
						)
						);
					} catch (Exception $e) {
						throw $e;
					}
				} else {
					try {
						Circles::createCircle(Env::listCircleTypes()[$i], $circleNames[$j]);
						$this->assertSame(
							true, false, 'should return an exception'
						);
					} catch (CircleAlreadyExistsException $e) {
					} catch (Exception $e) {
						$this->assertSame(
							true, false,
							'should have returned a CircleAlreadyExistsException'
						);
					}
				}
			}
		}

		foreach ($circles as $circle) {
			Circles::destroyCircle($circle->getId());
		}

		Env::logout();
	}


	/**
	 * In this test, we will add user to circle, check their level and rights and remove them
	 * before checking their rights again.
	 */
	public function testAddAndRemoveUser() {
		Env::setUser(Env::ENV_TEST_OWNER1);

		for ($i = 0; $i < 3; $i++) {
			foreach ($this->circles as $circle) {
				try {
					$member = Circles::getMember(
						$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
					);
					$this->assertEquals(
						[
							Env::ENV_TEST_MEMBER2, DeprecatedMember::LEVEL_NONE, DeprecatedMember::STATUS_NONMEMBER,
							$circle->getId()
						]
						, [
							$member->getUserId(), $member->getLevel(), $member->getStatus(),
							$member->getCircleId()
						]
					);
				} catch (MemberDoesNotExistException $e) {
				} catch (Exception $e) {
					throw $e;
				}


				try {
					Circles::addMember($circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER);

					// If Closed, we check that the user is not a member before confirming
					// the invitation using member account
					if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED) {
						$member = Circles::getMember(
							$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
						);
						$this->assertEquals(
							[
								Env::ENV_TEST_MEMBER2, DeprecatedMember::LEVEL_NONE, DeprecatedMember::STATUS_INVITED,
								$circle->getId()
							]
							, [
								$member->getUserId(), $member->getLevel(), $member->getStatus(),
								$member->getCircleId()
							]
						);

						Env::setUser(Env::ENV_TEST_MEMBER2);
						Circles::joinCircle($circle->getId());
						Env::setUser(Env::ENV_TEST_OWNER1);
					}

					$member = Circles::getMember(
						$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
					);
					$this->assertEquals(
						[
							Env::ENV_TEST_MEMBER2, DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::STATUS_MEMBER,
							$circle->getId()
						]
						, [
							$member->getUserId(), $member->getLevel(), $member->getStatus(),
							$member->getCircleId()
						]
					);


					Circles::removeMember(
						$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
					);

					try {
						$member = Circles::getMember(
							$circle->getId(), Env::ENV_TEST_MEMBER2, DeprecatedMember::TYPE_USER
						);
						$this->assertEquals(
							[
								Env::ENV_TEST_MEMBER2, DeprecatedMember::LEVEL_NONE, DeprecatedMember::STATUS_NONMEMBER,
								$circle->getId()
							]
							, [
								$member->getUserId(), $member->getLevel(), $member->getStatus(),
								$member->getCircleId()
							]
						);
					} catch (MemberDoesNotExistException $e) {
					}
				} catch (Exception $e) {
					throw $e;
				}
			}
		}

		Env::logout();
	}


	/**
	 * We check the join/leave and the rights of a member during the process.
	 *
	 * @throws Exception
	 */
	public function testJoinCircleAndLeave() {
		Env::setUser(Env::ENV_TEST_MEMBER3);

		for ($i = 0; $i < 3; $i++) {
			foreach ($this->circles as $circle) {
				try {
					$member = Circles::getMember(
						$circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER
					);
					$this->assertEquals(
						[
							Env::ENV_TEST_MEMBER3, DeprecatedMember::LEVEL_NONE, DeprecatedMember::STATUS_NONMEMBER,
							$circle->getId()
						]
						, [
							$member->getUserId(), $member->getLevel(), $member->getStatus(),
							$member->getCircleId()
						]
					);
				} catch (MemberDoesNotExistException $e) {
					if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
						throw $e;
					}
				} catch (CircleDoesNotExistException $f) {
					if ($circle->getType() !== DeprecatedCircle::CIRCLES_PERSONAL) {
						throw $f;
					}
				} catch (Exception $e) {
					throw $e;
				}


				if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
					try {
						Circles::joinCircle($circle->getId());
						$this->assertSame(
							true, false, 'should return an exception'
						);
					} catch (CircleDoesNotExistException $e) {
					} catch (Exception $e) {
						$this->assertSame(
							true, false, 'should have returned a CircleDoesNotExistException'
						);
					}
				} else {
					Circles::joinCircle($circle->getId());


					try {

						// If Closed, we check that the user is not a member before accepting
						// the request using a moderator account
						if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED) {
							Env::setUser(Env::ENV_TEST_OWNER1);
							$member = Circles::getMember(
								$circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER
							);
							$this->assertEquals(
								[
									Env::ENV_TEST_MEMBER3, DeprecatedMember::LEVEL_NONE,
									DeprecatedMember::STATUS_REQUEST,
									$circle->getId()
								]
								, [
									$member->getUserId(), $member->getLevel(), $member->getStatus(),
									$member->getCircleId()
								]
							);

							Circles::addMember($circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER);
							Env::setUser(Env::ENV_TEST_MEMBER3);
						}

						$member = Circles::getMember(
							$circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER
						);
						$this->assertEquals(
							[
								Env::ENV_TEST_MEMBER3, DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::STATUS_MEMBER,
								$circle->getId()
							]
							, [
								$member->getUserId(), $member->getLevel(), $member->getStatus(),
								$member->getCircleId()
							]
						);
					} catch (Exception $e) {
						throw $e;
					}

					Circles::leaveCircle($circle->getId());

					// We check the member have no access to the circle
					try {
						Circles::getMember(
							$circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER
						);
						$this->assertSame(
							true, false, 'should return an exception'
						);
					} catch (MemberDoesNotExistException $e) {
					} catch (Exception $e) {
						$this->assertSame(
							true, false, 'should have returned a MemberDoesNotExistException'
						);
					}

					// We check that the user is not a member from the owner PoV
					Env::setUser(Env::ENV_TEST_OWNER1);
					try {
						$member = Circles::getMember(
							$circle->getId(), Env::ENV_TEST_MEMBER3, DeprecatedMember::TYPE_USER
						);
						$this->assertEquals(
							[
								Env::ENV_TEST_MEMBER3, DeprecatedMember::LEVEL_NONE, DeprecatedMember::STATUS_NONMEMBER,
								$circle->getId()
							]
							, [
								$member->getUserId(), $member->getLevel(), $member->getStatus(),
								$member->getCircleId()
							]
						);
					} catch (MemberDoesNotExistException $e) {
					} catch (Exception $e) {
						throw $e;
					}
					Env::setUser(Env::ENV_TEST_MEMBER3);
				}
			}
		}

		Env::logout();
	}


	/**
	 * Listing Circles, as a non-member and as a member
	 */
	public function testListCircles() {

		// First, we check from an outside PoV, user is not in any circles right now.
		Env::setUser(Env::ENV_TEST_MEMBER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_ALL);
		$this->assertCount(2, $listing);

		$result = [];
		foreach ($listing as $circle) {
			array_push($result, $circle->getName());
		}

		$this->assertEquals($result, [self::NAME_PUBLIC_CIRCLE1, self::NAME_CLOSED_CIRCLE1]);


		// Let's add user to all circle
		Env::setUser(Env::ENV_TEST_OWNER1);
		$circles = [$this->circles['Public'], $this->circles['Closed'], $this->circles['Secret']];
		foreach ($circles as $circle) {
			$this->generateSimpleCircleWithAllLevel(
				$circle->getId(), ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED)
			);
		}


		// Let's check from an owner PoV
		Env::setUser(Env::ENV_TEST_OWNER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_ALL);
		$this->assertCount(4, $listing);

		$result = [];
		foreach ($listing as $circle) {
			array_push($result, $circle->getName());
		}

		$this->assertEquals(
			$result, [
				self::NAME_PUBLIC_CIRCLE1,
				self::NAME_SECRET_CIRCLE1,
				self::NAME_CLOSED_CIRCLE1,
				self::NAME_PERSONAL_CIRCLE1
			]
		);


		// check from a member PoV
		Env::setUser(Env::ENV_TEST_MEMBER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_ALL);
		$this->assertCount(3, $listing);

		$result = [];
		foreach ($listing as $circle) {
			array_push($result, $circle->getName());
		}

		$this->assertEquals(
			$result, [
				self::NAME_PUBLIC_CIRCLE1,
				self::NAME_SECRET_CIRCLE1,
				self::NAME_CLOSED_CIRCLE1
			]
		);


		// member with a dedicated search on secret
		Env::setUser(Env::ENV_TEST_MEMBER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE1);
		$this->assertCount(1, $listing);

		// member with a search on secret
		Env::setUser(Env::ENV_TEST_MEMBER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, '');
		$this->assertCount(1, $listing);

		// removing member from Circle
		Env::setUser(Env::ENV_TEST_OWNER1);
		Circles::removeMember(
			$this->circles['Secret']->getId(), Env::ENV_TEST_MEMBER1, DeprecatedMember::TYPE_USER
		);

		// member with a search on secret
		Env::setUser(Env::ENV_TEST_MEMBER1);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, '');
		$this->assertCount(0, $listing);

		// non-member with a dedicated search on secret
		Env::setUser(Env::ENV_TEST_MEMBER2);

		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE1);
		$this->assertCount(1, $listing);

		// member with a dedicated search on personal
		Env::setUser(Env::ENV_TEST_MEMBER1);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1);
		$this->assertCount(0, $listing);

		// non-member with a dedicated search on personal
		Env::setUser(Env::ENV_TEST_MEMBER2);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1);
		$this->assertCount(0, $listing);

		// few request as another Owner on secret
		Env::SetUser(Env::ENV_TEST_OWNER2);
		$circle = Circles::createCircle(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE2);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, '');
		$this->assertCount(1, $listing);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE1);
		$this->assertCount(1, $listing);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_SECRET, self::NAME_SECRET_CIRCLE2);
		$this->assertCount(1, $listing);
		Circles::destroyCircle($circle->getId());

		// few request as another Owner on personal
		Env::SetUser(Env::ENV_TEST_OWNER2);
		$circle = Circles::createCircle(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE2);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_PERSONAL, '');
		$this->assertCount(1, $listing);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE1);
		$this->assertCount(0, $listing);
		$listing = Circles::listCircles(DeprecatedCircle::CIRCLES_PERSONAL, self::NAME_PERSONAL_CIRCLE2);
		$this->assertCount(1, $listing);
		Circles::destroyCircle($circle->getId());

		Env::logout();
	}


	public function testDetailsCircle() {
	}


	/**
	 * function to generate admin/moderator/member and assigning them their level.
	 *
	 * @param $circleId
	 * @param bool $isClosed
	 *
	 * @throws QueryException
	 */
	protected function generateSimpleCircleWithAllLevel($circleId, $isClosed = false) {
		$curr = Env::currentUser();

		Circles::addMember($circleId, Env::ENV_TEST_ADMIN1, DeprecatedMember::TYPE_USER);
		if ($isClosed) {
			Env::setUser(Env::ENV_TEST_ADMIN1);
			Circles::joinCircle($circleId);
			Env::setUser($curr);
		}
		Circles::levelMember(
			$circleId, Env::ENV_TEST_ADMIN1, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_ADMIN
		);


		Circles::addMember($circleId, Env::ENV_TEST_MODERATOR1, DeprecatedMember::TYPE_USER);
		if ($isClosed) {
			Env::setUser(Env::ENV_TEST_MODERATOR1);
			Circles::joinCircle($circleId);
			Env::setUser($curr);
		}
		Circles::levelMember(
			$circleId, Env::ENV_TEST_MODERATOR1, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_MODERATOR
		);

		Circles::addMember($circleId, Env::ENV_TEST_MEMBER1, DeprecatedMember::TYPE_USER);
		if ($isClosed) {
			Env::setUser(Env::ENV_TEST_MEMBER1);
			Circles::joinCircle($circleId);
			Env::setUser($curr);
		}
		Circles::levelMember(
			$circleId, Env::ENV_TEST_MEMBER1, DeprecatedMember::TYPE_USER, DeprecatedMember::LEVEL_MEMBER
		);
	}
}
