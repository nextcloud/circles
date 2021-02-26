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


namespace OCA\Circles\Db;


use daita\MySmallPhpTools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Model\Federated\FederatedShare;

/**
 * Class ShareRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareLockRequestBuilder extends CoreQueryBuilder {


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareLockInsertSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE_LOCKS);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareLockSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();

		$qb->select('s.id', 's.item_id', 's.circle_id', 's.instance')
		   ->from(self::TABLE_SHARE_LOCKS, 's')
		   ->setDefaultSelectAlias('s');

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareLockUpdateSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE_LOCKS);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareDeleteSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE_LOCKS);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return FederatedShare
	 * @throws FederatedShareNotFoundException
	 */
	public function getItemFromRequest(CoreRequestBuilder $qb): FederatedShare {
		/** @var FederatedShare $circle */
		try {
			$circle = $qb->asItem(
				FederatedShare::class,
				[
					'local' => $this->configService->getLocalInstance()
				]
			);
		} catch (RowNotFoundException $e) {
			throw new FederatedShareNotFoundException();
		}

		return $circle;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return FederatedShare[]
	 */
	public function getItemsFromRequest(CoreRequestBuilder $qb): array {
		/** @var FederatedShare[] $result */
		return $qb->asItems(
			FederatedShare::class,
			[
				'local' => $this->configService->getLocalInstance()
			]
		);
	}


}
