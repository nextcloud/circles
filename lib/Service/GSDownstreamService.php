<?php

declare(strict_types=1);


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


namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Exceptions\GSKeyException;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCP\IURLGenerator;

/**
 * Class GSDownstreamService
 *
 * @package OCA\Circles\Service
 */
class GSDownstreamService {
	/** @var string */
	private $userId = '';

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var GlobalScaleService */
	private $globalScaleService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GSUpstreamService constructor.
	 *
	 * @param $userId
	 * @param IURLGenerator $urlGenerator
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param GlobalScaleService $globalScaleService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IURLGenerator $urlGenerator,
		EventWrapperRequest $eventWrapperRequest,
		DeprecatedCirclesRequest $circlesRequest,
		GlobalScaleService $globalScaleService,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->circlesRequest = $circlesRequest;
		$this->globalScaleService = $globalScaleService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSKeyException
	 * @throws GlobalScaleEventException
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 */
	public function requestedEvent(GSEvent $event) {
		$this->globalScaleService->checkEvent($event);

		$gs = $this->globalScaleService->getGlobalScaleEvent($event);
		$gs->verify($event, true);

		if (!$event->isAsync()) {
			$gs->manage($event);
		}

		$this->globalScaleService->asyncBroadcast($event);
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GSKeyException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function statusEvent(GSEvent $event) {
		$this->globalScaleService->checkEvent($event);

		$gs = $this->globalScaleService->getGlobalScaleEvent($event);
		$gs->verify($event, false);
		$gs->manage($event);
	}


	/**
	 * @param GSEvent $event
	 */
	public function onNewEvent(GSEvent $event) {
		try {
			$this->globalScaleService->checkEvent($event);

			$gs = $this->globalScaleService->getGlobalScaleEvent($event);
			$gs->manage($event);
		} catch (Exception $e) {
			$this->miscService->log('issue onNewEvent: ' . json_encode($event) . ' - ' . $e->getMessage());
		}
	}
}
