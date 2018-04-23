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


use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\FederatedLinkDoesNotExistException;
use OCA\Circles\Exceptions\SharingFrameDoesNotExistException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Model\Timezone;

class SharingFrameRequest extends SharingFrameRequestBuilder {


	/**
	 * @param string $circleUniqueId
	 * @param string $frameUniqueId
	 *
	 * @return SharingFrame
	 * @throws SharingFrameDoesNotExistException
	 */
	public function getSharingFrame($circleUniqueId, $frameUniqueId) {
		$qb = $this->getSharesSelectSql();
		$this->limitToUniqueId($qb, $frameUniqueId);
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->leftJoinCircle($qb);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new SharingFrameDoesNotExistException($this->l10n->t('Sharing Frame does not exist'));
		}

		$entry = $this->parseSharesSelectSql($data);

		return $entry;
	}


	/**
	 * @param string $circleUniqueId
	 *
	 * @return SharingFrame[]
	 */
	public function getSharingFramesFromCircle($circleUniqueId) {
		$qb = $this->getSharesSelectSql();
		$this->limitToCircleId($qb, $circleUniqueId);

		$frames = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$frames[] = $this->parseSharesSelectSql($data);
		}
		$cursor->closeCursor();

		return $frames;
	}


	/**
	 * saveFrame()
	 *
	 * Insert a new entry in the database to save the SharingFrame.
	 *
	 * @param SharingFrame $frame
	 */
	public function saveSharingFrame(SharingFrame $frame) {
		$qb = $this->getSharesInsertSql();
		$circle = $frame->getCircle();
		$qb->setValue('circle_id', $qb->createNamedParameter($circle->getUniqueId()))
		   ->setValue('source', $qb->createNamedParameter($frame->getSource()))
		   ->setValue('type', $qb->createNamedParameter($frame->getType()))
		   ->setValue('headers', $qb->createNamedParameter($frame->getHeaders(true)))
		   ->setValue('author', $qb->createNamedParameter($frame->getAuthor()))
		   ->setValue('cloud_id', $qb->createNamedParameter($frame->getCloudId()))
		   ->setValue('unique_id', $qb->createNamedParameter($frame->getUniqueId()))
		   ->setValue('payload', $qb->createNamedParameter($frame->getPayload(true)))
		   ->setValue('creation', $qb->createNamedParameter(Timezone::getUTCTimestamp()));

		$qb->execute();
	}


	public function updateSharingFrame(SharingFrame $frame) {
		$qb = $this->getSharesUpdateSql($frame->getUniqueId());
		$circle = $frame->getCircle();
		$qb->set('circle_id', $qb->createNamedParameter($circle->getUniqueId()))
		   ->set('source', $qb->createNamedParameter($frame->getSource()))
		   ->set('type', $qb->createNamedParameter($frame->getType()))
		   ->set('headers', $qb->createNamedParameter($frame->getHeaders(true)))
		   ->set('author', $qb->createNamedParameter($frame->getAuthor()))
		   ->set('cloud_id', $qb->createNamedParameter($frame->getCloudId()))
		   ->set('unique_id', $qb->createNamedParameter($frame->getUniqueId()))
		   ->set('payload', $qb->createNamedParameter($frame->getPayload(true)));

		$qb->execute();
	}


}