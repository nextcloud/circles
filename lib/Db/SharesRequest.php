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


namespace OCA\Circles\Db;


use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\Member;


/**
 * Class SharesRequest
 *
 * @package OCA\Circles\Db
 */
class SharesRequest extends SharesRequestBuilder {


	use TArrayTools;
	use TStringTools;


	/**
	 * remove shares from a member to a circle
	 *
	 * @param Member $member
	 */
	public function removeSharesFromMember(Member $member) {
		$qb = $this->getSharesDeleteSql();
		$expr = $qb->expr();

		$andX = $expr->andX();
		$andX->add($expr->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE)));
		$andX->add($expr->eq('share_with', $qb->createNamedParameter($member->getCircleId())));
		$andX->add($expr->eq('uid_initiator', $qb->createNamedParameter($member->getUserId())));
		$qb->andWhere($andX);

		$qb->execute();
	}


	/**
	 * @param int $shareId
	 *
	 * @return string
	 * @throws TokenDoesNotExistException
	 */
	public function getTokenByShareId(int $shareId) {
		$qb = $this->getSharesSelectSql();
		$this->limitToId($qb, $shareId);
		$this->limitToShareType($qb, self::SHARE_TYPE);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new TokenDoesNotExistException('Unknown share token');
		}

		return $this->get('token', $data, 'notfound');
	}


	/**
	 * @param string $circleUniqueId
	 */
	public function shuffleTokensFromCircle(string $circleUniqueId) {
		$shares = $this->getSharesForCircle($circleUniqueId);
		foreach ($shares as $share) {
			$this->shuffleTokenByShareId($this->getInt('id', $share, 0));
		}
	}


	/**
	 * @param int $shareId
	 */
	public function shuffleTokenByShareId(int $shareId) {
		$qb = $this->getSharesUpdateSql();
		$qb->set('token', $qb->createNamedParameter($this->uuid(15)));

		$this->limitToShareType($qb, self::SHARE_TYPE);
		$this->limitToId($qb, $shareId);

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 */
	public function getSharesForCircle(string $circleId) {
		$qb = $this->getSharesSelectSql();

		$this->limitToShareWith($qb, $circleId);
		$this->limitToShareType($qb, self::SHARE_TYPE);

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $data;
		}
		$cursor->closeCursor();

		return $shares;
	}


}
