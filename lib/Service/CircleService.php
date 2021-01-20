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


namespace OCA\Circles\Service;


use daita\MySmallPhpTools\Traits\TArrayTools;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class CircleService
 *
 * @package OCA\Circles\Service
 */
class CircleService {


	use TArrayTools;


	/** @var CircleRequest */
	private $circleRequest;


	/** @var Member */
	private $viewer = null;


	/**
	 * CircleService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 */
	public function __construct(CircleRequest $circleRequest) {
		$this->circleRequest = $circleRequest;
	}


	/**
	 * @param string $userId
	 */
	public function setLocalViewer(string $userId): void {
		$this->viewer = new Member($userId, Member::TYPE_USER, '');
	}

	public function setViewer(Member $viewer): void {
		$this->viewer = $viewer;
	}

	/**
	 * @return Member|null
	 */
	public function getViewer(): ?Member {
		return $this->viewer;
	}


	/**
	 * @param Member|null $filter
	 *
	 * @return Circle[]
	 */
	public function getCircles(?Member $filter = null): array {
		return $this->circleRequest->getCircles($this->getViewer(), $filter);
	}

}

