<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles;


use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
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

	/** @var CoreQueryBuilder */
	private $queryBuilder;

	/**
	 * CirclesQueryHelper constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder
	) {
		$this->coreRequestBuilder = $coreRequestBuilder;
	}


	/**
	 * @return IQueryBuilder
	 */
	public function getQueryBuilder(): IQueryBuilder {
		$this->queryBuilder = $this->coreRequestBuilder->getQueryBuilder();

		return $this->queryBuilder;
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
		bool $fullDetails = false
	): ICompositeExpression {
		$this->queryBuilder->setBypassAliasGeneration(true);
		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData'      => $fullDetails,
				'mustBeMember' => true
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
	 * @param string $field
	 * @param string $alias
	 *
	 * @throws RequestBuilderException
	 */
	public function addCircleDetails(
		string $alias,
		string $field
	): void {
		$this->queryBuilder->setBypassAliasGeneration(true);
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

