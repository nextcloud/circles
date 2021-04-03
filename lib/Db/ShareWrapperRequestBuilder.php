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
class ShareWrapperRequestBuilder extends CoreQueryBuilder {


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareInsertSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareUpdateSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getShareSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select(
			CoreRequestBuilder::SHARE . '.id',
			CoreRequestBuilder::SHARE . '.share_type',
			CoreRequestBuilder::SHARE . '.share_with',
			CoreRequestBuilder::SHARE . '.uid_owner',
			CoreRequestBuilder::SHARE . '.uid_initiator',
			CoreRequestBuilder::SHARE . '.parent',
			CoreRequestBuilder::SHARE . '.item_type',
			CoreRequestBuilder::SHARE . '.item_source',
			CoreRequestBuilder::SHARE . '.item_target',
			CoreRequestBuilder::SHARE . '.file_source',
			CoreRequestBuilder::SHARE . '.file_target',
			CoreRequestBuilder::SHARE . '.permissions',
			CoreRequestBuilder::SHARE . '.stime',
			CoreRequestBuilder::SHARE . '.accepted',
			CoreRequestBuilder::SHARE . '.expiration',
			CoreRequestBuilder::SHARE . '.token',
			CoreRequestBuilder::SHARE . '.mail_send'
		)
		   ->from(self::TABLE_SHARE, CoreRequestBuilder::SHARE)
		   ->setDefaultSelectAlias(CoreRequestBuilder::SHARE)
		   ->limitToShareType(Share::TYPE_CIRCLE);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreRequestBuilder
	 */
	protected function getShareDeleteSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE)
		   ->limitToShareType(Share::TYPE_CIRCLE);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreRequestBuilder $qb): ShareWrapper {
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
	 * @param CoreRequestBuilder $qb
	 *
	 * @return ShareWrapper[]
	 */
	public function getItemsFromRequest(CoreRequestBuilder $qb): array {
		/** @var ShareWrapper[] $result */
		return $qb->asItems(
			ShareWrapper::class,
			[
				'local' => $this->configService->getFrontalInstance()
			]
		);
	}

}

