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
use OC\Share20\Share;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\ShareWrapper;


/**
 * Class ShareWrapperRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareWrapperRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select(
			CoreQueryBuilder::SHARE . '.id',
			CoreQueryBuilder::SHARE . '.share_type',
			CoreQueryBuilder::SHARE . '.share_with',
			CoreQueryBuilder::SHARE . '.uid_owner',
			CoreQueryBuilder::SHARE . '.uid_initiator',
			CoreQueryBuilder::SHARE . '.parent',
			CoreQueryBuilder::SHARE . '.item_type',
			CoreQueryBuilder::SHARE . '.item_source',
			CoreQueryBuilder::SHARE . '.item_target',
			CoreQueryBuilder::SHARE . '.file_source',
			CoreQueryBuilder::SHARE . '.file_target',
			CoreQueryBuilder::SHARE . '.permissions',
			CoreQueryBuilder::SHARE . '.stime',
			CoreQueryBuilder::SHARE . '.accepted',
			CoreQueryBuilder::SHARE . '.expiration',
			CoreQueryBuilder::SHARE . '.token',
			CoreQueryBuilder::SHARE . '.mail_send'
		)
		   ->from(self::TABLE_SHARE, CoreQueryBuilder::SHARE)
		   ->setDefaultSelectAlias(CoreQueryBuilder::SHARE)
		   ->groupBy(CoreQueryBuilder::SHARE . '.id')
		   ->limitToShareType(Share::TYPE_CIRCLE);


		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getShareDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE)
		   ->limitToShareType(Share::TYPE_CIRCLE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): ShareWrapper {
		/** @var ShareWrapper $shareWrapper */
		try {
			$shareWrapper = $qb->asItem(
				ShareWrapper::class,
				[
					'local' => $this->configService->getFrontalInstance()
				]
			);
		} catch (RowNotFoundException $e) {
			throw new ShareWrapperNotFoundException();
		}

		return $shareWrapper;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareWrapper[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var ShareWrapper[] $result */
		return $qb->asItems(
			ShareWrapper::class,
			[
				'local' => $this->configService->getFrontalInstance()
			]
		);
	}

}

