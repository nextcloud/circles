<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests;

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\DataProbe;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class CirclesManagerTest extends TestCase {
	private CirclesManager $circlesManager;
	private string $userId = 'circles-testuser';
	private string $groupId = 'circles-testgroup';
	private string $circleName;

	public function setUp(): void {
		parent::setUp();

		// Random circle name
		$this->circleName = sha1(uniqId(mt_rand(), true));

		// Create test user
		$userManager = Server::get(IUserManager::class);
		if (!$userManager->userExists($this->userId)) {
			$user = $userManager->createUser($this->userId, $this->userId);
		} else {
			$user = $userManager->get($this->userId);
		}

		// Create test group and add user
		$groupManager = Server::get(IGroupManager::class);
		if (!$groupManager->groupExists($this->groupId)) {
			$group = $groupManager->createGroup($this->groupId);
			$group->addUser($user);
		}

		$this->circlesManager = Server::get(CirclesManager::class);

	}

	// Start user session as user (default: test user)
	private function startSession(?string $userId = null): FederatedUser {
		if (!$userId) {
			$userId = $this->userId;
		}
		$federatedUser = $this->circlesManager->getLocalFederatedUser($userId);
		$this->circlesManager->startSession($federatedUser, true);
		return $federatedUser;
	}

	public function testCreateCircle(): void {
		$federatedUser = $this->startSession();

		// Created circle has properties
		$circle = $this->circlesManager->createCircle($this->circleName);
		$this->assertEquals($this->circleName, $circle->getName());
		$this->assertEquals($this->circleName, $circle->getDisplayName());
		$this->assertEquals($this->circleName, $circle->getSanitizedName());
		$this->assertEquals($federatedUser->getSingleId(), $circle->getOwner()->getSingleId());
		$this->assertEquals($federatedUser->getSingleId(), $circle->getInitiator()->getSingleId());

		// Created circle returned by probeCircle()
		$circles = $this->circlesManager->probeCircles();
		$this->assertCount(1, array_filter($circles, function (Circle $c) { return $c->getName() === $this->circleName; }));

		// Destroyed circle not returned by probeCircle()
		$this->circlesManager->destroyCircle($circle->getSingleId());
		$circles = $this->circlesManager->probeCircles();
		$this->assertCount(0, array_filter($circles, function (Circle $c) { return $c->getName() === $this->circleName; }));
	}

	public function testGetCirclesWithInitiator(): void {
		// Create circle as user 'admin' and add test user as member
		$this->startSession('admin');
		$adminCircle = $this->circlesManager->createCircle($this->circleName);
		$this->circlesManager->addMember($adminCircle->getSingleId(), $this->circlesManager->getLocalFederatedUser($this->userId));

		// Get circles as test user
		$federatedUser = $this->startSession();
		$circles = $this->circlesManager->getCircles();
		$circle = null;
		foreach ($circles as $c) {
			if ($c->getSingleId() === $adminCircle->getSingleId()) {
				$circle = $c;
			}
		}

		// Initiator of probed circle has correct properties
		$this->assertEquals($federatedUser->getSingleId(), $circle->getInitiator()->getSingleId());
		$this->assertEquals(1, $circle->getInitiator()->getLevel());

		// Destroy circle as user 'admin'
		$this->startSession('admin');
		$this->circlesManager->destroyCircle($adminCircle->getSingleId());
	}

	public function testProbeCirclesWithInitiator(): void {
		// Create circle as user 'admin' and add test user as member
		$this->startSession('admin');
		$adminCircle = $this->circlesManager->createCircle($this->circleName);
		$this->circlesManager->addMember($adminCircle->getSingleId(), $this->circlesManager->getLocalFederatedUser($this->userId));

		// Probe circles as test user
		$federatedUser = $this->startSession();
		$dataProbe = new DataProbe();
		$dataProbe->add(DataProbe::INITIATOR);
		$circles = $this->circlesManager->probeCircles(null, $dataProbe);
		$circle = null;
		foreach ($circles as $c) {
			if ($c->getSingleId() === $adminCircle->getSingleId()) {
				$circle = $c;
			}
		}

		// Initiator of probed circle has correct properties
		$this->assertEquals($federatedUser->getSingleId(), $circle->getInitiator()->getSingleId());
		$this->assertEquals(1, $circle->getInitiator()->getLevel());

		// Destroy circle as user 'admin'
		$this->startSession('admin');
		$this->circlesManager->destroyCircle($adminCircle->getSingleId());
	}

	public function testProbeCirclesWithInitiatorAsGroupMember(): void {
		// Create circle as user 'admin' and add test group as member
		$this->startSession('admin');
		$adminCircle = $this->circlesManager->createCircle($this->circleName);
		$federatedGroup = $this->circlesManager->getFederatedUser($this->groupId, Member::TYPE_GROUP);
		$this->circlesManager->addMember($adminCircle->getSingleId(), $federatedGroup);

		// Probe circles as test user
		$federatedUser = $this->startSession();
		$dataProbe = new DataProbe();
		$dataProbe->add(DataProbe::INITIATOR);
		$circles = $this->circlesManager->probeCircles(null, $dataProbe);
		$circle = null;
		foreach ($circles as $c) {
			if ($c->getSingleId() === $adminCircle->getSingleId()) {
				$circle = $c;
			}
		}

		// Initiator of probed circle has correct properties
		$this->assertEquals($federatedGroup->getSingleId(), $circle->getInitiator()->getSingleId());
		$this->assertEquals(1, $circle->getInitiator()->getLevel());

		// Destroy circle as user 'admin'
		$this->startSession('admin');
		$this->circlesManager->destroyCircle($adminCircle->getSingleId());
	}
}
