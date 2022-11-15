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

use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\ShareToken;

/**
 * Class ShareTokenRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareTokenRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder
	 */
	protected function getTokenInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getTokenUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getTokenSelectSql(string $alias = CoreQueryBuilder::TOKEN): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TOKEN, self::$tables[self::TABLE_TOKEN], $alias);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getTokenDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareToken
	 * @throws ShareTokenNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): ShareToken {
		/** @var ShareToken $shareToken */
		try {
			$shareToken = $qb->asItem(ShareToken::class);
		} catch (RowNotFoundException $e) {
			throw new ShareTokenNotFoundException();
		}

		return $shareToken;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareToken[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var ShareToken[] $result */
		return $qb->asItems(ShareToken::class);
	}
}
