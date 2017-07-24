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
	 * @param string $uniqueId
	 * @param string $source
	 * @param string $type
	 * @param array $payload
	 *
	 * @return DataResponse
	 */
	public function create($uniqueId, $source, $type, $payload) {

		try {
			$share = new SharingFrame($source, $type);
			$share->setCircleId($uniqueId);
			$share->setPayload($payload);

			$this->sharesService->createFrame($share);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'source'    => $source,
					'type'      => $type,
					'payload'   => $payload,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'source'    => $source,
				'type'      => $type,
				'payload'   => $payload
			]
		);
	}


}

