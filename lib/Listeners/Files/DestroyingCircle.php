<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Files;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<DestroyingCircleEvent|Event> */
class DestroyingCircle implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var ShareWrapperService */
	private $shareWrapperService;


	/**
	 * AddingMember constructor.
	 *
	 * @param ShareWrapperService $shareWrapperService
	 */
	public function __construct(ShareWrapperService $shareWrapperService) {
		$this->shareWrapperService = $shareWrapperService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @throws RequestBuilderException
	 */
	public function handle(Event $event): void {
		if (!$event instanceof DestroyingCircleEvent) {
			return;
		}

		$circle = $event->getCircle();
		$this->shareWrapperService->deleteAllSharesToCircle($circle->getSingleId());
	}
}
