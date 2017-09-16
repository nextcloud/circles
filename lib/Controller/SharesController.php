<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\Model\SharingFrame;
use OCP\AppFramework\Http\DataResponse;

class SharesController extends BaseController {


	/**
	 * Called by the JavaScript API when creating a new Share item that will be
	 * broadcasted to the circle itself, and any other circle linked to it.
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @deprecated - should not be possible to create a SharingFrame from JS (source/type not verified)
	 *
	 * @param string $circleUniqueId
	 * @param string $source
	 * @param string $type
	 * @param array $payload
	 *
	 * @return DataResponse
	 */
	public function create($circleUniqueId, $source, $type, $payload) {

		try {
			$share = new SharingFrame($source, $type);
			$share->setPayload($payload);

			$this->sharingFrameService->createFrame($circleUniqueId, $share);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $circleUniqueId,
					'source'    => $source,
					'type'      => $type,
					'payload'   => $payload,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $circleUniqueId,
				'source'    => $source,
				'type'      => $type,
				'payload'   => $payload
			]
		);
	}


	/**
	 * initShareDelivery()
	 *
	 * Note: this function will close the request mid-run from the client but will still
	 * running its process.
	 *
	 * Called by locally, the function will get the SharingFrame by its uniqueId from the database.
	 * After closing the socket, will broadcast the Frame locally and - if Federated Shares are
	 * enable - will deliver it to each remote circles linked to the circle the Payload belongs to.
	 *
	 * A status response is sent to free the client process before starting to broadcast the item
	 * to other federated links.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $circleId
	 * @param string $frameId
	 *
	 * @return DataResponse
	 */
	public function initShareDelivery($circleId, $frameId) {

		try {
			$frame = $this->sharingFrameService->getFrameFromUniqueId($circleId, $frameId);
		} catch (Exception $e) {
			return $this->fail($e->getMessage());
		}

		// We don't want to keep the connection up
		$this->miscService->asyncAndLeaveClientOutOfThis('done');

		$this->broadcastService->localFrameBroadcast($frame);

		$this->sharingFrameService->updateFrameWithCloudId($frame);
		if ($this->configService->isFederatedCirclesAllowed()) {
			$this->sharingFrameService->forwardSharingFrame($frame);
		}

		exit();
	}

}

