<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CirclesQueryHelper
 *
 * @package OCA\Circles
 */
class CirclesQueryHelper {
	/** @var CoreRequestBuilder */
	private $coreRequestBuilder;

	/** @var CoreQueryBuilder&IQueryBuilder */
	private $queryBuilder;

	/** @var FederatedUserService */
	private $federatedUserService;


	/**
	 * CirclesQueryHelper constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param FederatedUserService $federatedUserService
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder,
		FederatedUserService $federatedUserService,
	) {
		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->federatedUserService = $federatedUserService;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	public function getQueryBuilder(): CoreQueryBuilder {
		$this->queryBuilder = $this->coreRequestBuilder->getQueryBuilder();

		return $this->queryBuilder;
	}


	/**
	 * @param string $alias
	 * @param string $field
	 * @param bool $fullDetails
	 *
	 * @return ICompositeExpression
	 * @throws RequestBuilderException
	 * @throws FederatedUserNotFoundException
	 */
	public function limitToSession(
		string $alias,
		string $field,
		bool $fullDetails = false,
	): ICompositeExpression {
		$session = $this->federatedUserService->getCurrentUser();
		if (is_null($session)) {
			throw new FederatedUserNotFoundException('session not initiated');
		}

		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => $fullDetails,
				'minimumLevel' => Member::LEVEL_MEMBER
			]
		);

		return $this->queryBuilder->limitToInitiator(
			CoreQueryBuilder::HELPER,
			$session,
			$field,
			$alias
		);
	}


	/**
	 * @param string $alias
	 * @param string $field
	 * @param IFederatedUser $federatedUser
	 * @param bool $fullDetails
	 *
	 * @return ICompositeExpression
	 * @throws RequestBuilderException
	 */
	public function limitToInheritedMembers(
		string $alias,
		string $field,
		IFederatedUser $federatedUser,
		bool $fullDetails = false,
	): ICompositeExpression {
		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => $fullDetails,
				'filterPersonalCircles' => true,
				'includePersonalCircles' => true,
				'minimumLevel' => Member::LEVEL_MEMBER
			]
		);

		return $this->queryBuilder->limitToInitiator(
			CoreQueryBuilder::HELPER,
			$federatedUser,
			$field,
			$alias
		);
	}

	/**
	 * lighter version with small inner join
	 */
	public function limitToMemberships(
		string $alias,
		string $field,
		IFederatedUser $federatedUser,
	): void {
		$this->queryBuilder->setDefaultSelectAlias($alias);
		$expr = $this->queryBuilder->expr();
		$aliasMembership = $this->queryBuilder->generateAlias(CoreQueryBuilder::HELPER, CoreQueryBuilder::MEMBERSHIPS, $options);

		$this->queryBuilder->innerJoin(
			$alias,
			CoreRequestBuilder::TABLE_MEMBERSHIP,
			$aliasMembership,
			$expr->andX(
				$this->queryBuilder->exprLimit('single_id', $federatedUser->getSingleId(), $aliasMembership),
				$expr->eq($aliasMembership . '.circle_id', $alias . '.' . $field)
			)
		);
	}


	/**
	 * @param string $field
	 * @param string $alias
	 *
	 * @throws RequestBuilderException
	 */
	public function addCircleDetails(
		string $alias,
		string $field,
	): void {
		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => true
			]
		);

		$this->queryBuilder->leftJoinCircle(CoreQueryBuilder::HELPER, null, $field, $alias);
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function extractCircle(array $data): Circle {
		$circle = new Circle();
		$circle->importFromDatabase(
			$data,
			CoreQueryBuilder::HELPER . '_' . CoreQueryBuilder::CIRCLE . '_'
		);

		return $circle;
	}
}
