<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Repository;

use OCA\Circles\Entity\Mountpoint;
use OCA\Circles\Exceptions\MountNotFoundException;
use OCA\Circles\Repository\MountpointRepository;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class MountpointRepositoryTest extends TestCase {
	private MountpointRepository $mountpointRepository;

	protected function setUp(): void {
		parent::setUp();

		$this->mountpointRepository = Server::get(MountpointRepository::class);
	}

	protected function tearDown(): void {
		$this->mountpointRepository->deleteBy(['mountId' => 'mount1234567890123456']);

		parent::tearDown();
	}

	public function testGetTableName(): void {
		$this->assertSame('circles_mountpoint', $this->mountpointRepository->getTableName());
	}

	public function testEntityClassPointsToMountpointEntity(): void {
		$this->assertSame(Mountpoint::class, MountpointRepository::entityClass);
	}

	public function testInsertMountpointComputesHash(): void {
		$mountpoint = new Mountpoint();
		$mountpoint->mountId = 'mount1234567890123456';
		$mountpoint->singleId = 'single1234567890123456';
		$mountpoint->mountPoint = 'Some Folder';

		$inserted = $this->mountpointRepository->insertMountpoint($mountpoint);

		$this->assertSame(md5('Some Folder'), $inserted->mountpointHash);

		$stored = $this->mountpointRepository->findOneBy(['mountId' => 'mount1234567890123456']);
		$this->assertSame('single1234567890123456', $stored->singleId);
		$this->assertSame('Some Folder', $stored->mountPoint);
		$this->assertSame(md5('Some Folder'), $stored->mountpointHash);
	}

	public function testInsertMountpointWithPlaceholderGetsEmptyHash(): void {
		$mountpoint = new Mountpoint();
		$mountpoint->mountId = 'mount1234567890123456';
		$mountpoint->singleId = 'single1234567890123456';
		$mountpoint->mountPoint = '-';

		$inserted = $this->mountpointRepository->insertMountpoint($mountpoint);

		$this->assertSame('', $inserted->mountpointHash);
	}

	public function testUpdateMountpointChangesMountPointAndHash(): void {
		$mountpoint = new Mountpoint();
		$mountpoint->mountId = 'mount1234567890123456';
		$mountpoint->singleId = 'single1234567890123456';
		$mountpoint->mountPoint = 'Original Name';
		$this->mountpointRepository->insertMountpoint($mountpoint);

		$update = new Mountpoint();
		$update->mountId = 'mount1234567890123456';
		$update->singleId = 'single1234567890123456';
		$update->mountPoint = 'Renamed Folder';
		$this->mountpointRepository->updateMountpoint($update);

		$stored = $this->mountpointRepository->findOneBy(['mountId' => 'mount1234567890123456']);
		$this->assertSame('Renamed Folder', $stored->mountPoint);
		$this->assertSame(md5('Renamed Folder'), $stored->mountpointHash);
	}

	public function testUpdateMountpointThrowsWhenNoRowMatched(): void {
		$mountpoint = new Mountpoint();
		$mountpoint->mountId = 'mount1234567890123456';
		$mountpoint->singleId = 'single1234567890123456';
		$mountpoint->mountPoint = 'Nothing to update';

		$this->expectException(MountNotFoundException::class);
		$this->mountpointRepository->updateMountpoint($mountpoint);
	}

	public function testFindOneByThrowsWhenNoRowMatched(): void {
		$this->expectException(DoesNotExistException::class);
		$this->mountpointRepository->findOneBy(['mountId' => 'mount1234567890123456']);
	}
}
