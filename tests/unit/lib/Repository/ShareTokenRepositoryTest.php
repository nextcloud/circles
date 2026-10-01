<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Repository;

use OC\AppFramework\ORM\EntityManager;
use OCA\Circles\Entity\ShareToken;
use OCA\Circles\Repository\ShareTokenRepository;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ShareTokenRepositoryTest extends TestCase {
	private IDBConnection&MockObject $connection;
	private ShareTokenRepository $shareTokenRepository;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);

		// EntityManager only reflects over the entity's attributes to resolve the table
		// name/columns; it needs a real instance (not a mock, as it's final) but never touches
		// $this->connection for that.
		$entityManager = new EntityManager($this->connection);

		$this->shareTokenRepository = new ShareTokenRepository($this->connection, $entityManager);
	}

	public function testGetTableName(): void {
		$this->assertSame('circles_token', $this->shareTokenRepository->getTableName());
	}

	public function testUpdateSharePassword(): void {
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$expressionBuilder = $this->createMock(IExpressionBuilder::class);

		$this->connection->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($queryBuilder);

		$queryBuilder->method('createNamedParameter')
			->willReturnCallback(static fn (mixed $value): string => (string)$value);
		$queryBuilder->method('expr')->willReturn($expressionBuilder);

		$queryBuilder->expects($this->once())
			->method('update')
			->with('circles_token')
			->willReturnSelf();

		$queryBuilder->expects($this->once())
			->method('set')
			->with('password', 'newHashedPassword')
			->willReturnSelf();

		$expressionBuilder->expects($this->once())
			->method('eq')
			->with('circle_id', 'theCircleId')
			->willReturn('circle_id = theCircleId');

		$queryBuilder->expects($this->once())
			->method('where')
			->with('circle_id = theCircleId')
			->willReturnSelf();

		$queryBuilder->expects($this->once())->method('executeStatement');

		$this->shareTokenRepository->updateSharePassword('theCircleId', 'newHashedPassword');
	}

	public function testEntityClassPointsToShareTokenEntity(): void {
		$this->assertSame(ShareToken::class, ShareTokenRepository::entityClass);
	}
}
