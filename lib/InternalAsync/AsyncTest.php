<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\Circles\InternalAsync;

use OCA\Circles\IInternalAsync;
use OCA\Circles\Service\AsyncService;
use OCA\Circles\Tools\Model\ReferencedDataStore;


class AsyncTest implements IInternalAsync {


	private AsyncService $asyncService;

	public function __construct(AsyncService $asyncService) {
		$this->asyncService = $asyncService;
	}


	public function runAsynced(ReferencedDataStore $store): void {

		\OC::$server->getLogger()->log(3, '-runAsynced ' . json_encode($store));
		$this->asyncService->asyncInternal(
			AsyncTest::class,
			new ReferencedDataStore(
				[
					'action' => 'test',
					'federatedUser' => $store->gObj('federatedUser')
				]
			)
		);
	}

}
