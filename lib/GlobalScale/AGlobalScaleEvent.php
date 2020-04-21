<?php declare(strict_types=1);


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


namespace OCA\Circles\GlobalScale;


use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\GSSharesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\GlobalScaleDSyncException;
use OCA\Circles\Exceptions\GlobalScaleEventException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventsService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCP\Defaults;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;


/**
 * Class AGlobalScaleEvent
 *
 * @package OCA\Circles\GlobalScale
 */
abstract class AGlobalScaleEvent {


	/** @var IRootFolder */
	protected $rootFolder;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IMailer */
	protected $mailer;

	/** @var Defaults */
	protected $defaults;

	/** @var IUserManager */
	protected $userManager;

	/** @var SharesRequest */
	protected $sharesRequest;

	/** @var TokensRequest */
	protected $tokensRequest;

	/** @var CirclesRequest */
	protected $circlesRequest;

	/** @var MembersRequest */
	protected $membersRequest;

	/** @var GSSharesRequest */
	protected $gsSharesRequest;

	/** @var CirclesService */
	protected $circlesService;

	/** @var MembersService */
	protected $membersService;

	/** @var EventsService */
	protected $eventsService;

	/** @var ConfigService */
	protected $configService;

	/** @var MiscService */
	protected $miscService;


	/**
	 * AGlobalScaleEvent constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IURLGenerator $urlGenerator
	 * @param IMailer $mailer
	 * @param Defaults $defaults
	 * @param IUserManager $userManager
	 * @param SharesRequest $sharesRequest
	 * @param TokensRequest $tokensRequest
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param GSSharesRequest $gsSharesRequest
	 * @param CirclesService $circlesService
	 * @param MembersService $membersService
	 * @param EventsService $eventsService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IURLGenerator $urlGenerator,
		IMailer $mailer,
		Defaults $defaults,
		IUserManager $userManager,
		SharesRequest $sharesRequest,
		TokensRequest $tokensRequest,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		GSSharesRequest $gsSharesRequest,
		CirclesService $circlesService,
		MembersService $membersService,
		EventsService $eventsService,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->userManager = $userManager;
		$this->sharesRequest = $sharesRequest;
		$this->tokensRequest = $tokensRequest;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->gsSharesRequest = $gsSharesRequest;
		$this->circlesService = $circlesService;
		$this->membersService = $membersService;
		$this->eventsService = $eventsService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 *
	 * @param bool $mustBeCheck
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeCheck = false): void {
		if ($localCheck && !$event->isForced()) {
			$this->checkViewer($event, $mustBeCheck);
		}
	}


	/**
	 * @param GSEvent $event
	 */
	abstract public function manage(GSEvent $event): void;


	/**
	 * @param GSEvent[] $events
	 */
	abstract public function result(array $events): void;


	/**
	 * @param GSEvent $event
	 * @param bool $mustBeChecked
	 *
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws GlobalScaleDSyncException
	 * @throws GlobalScaleEventException
	 */
	private function checkViewer(GSEvent $event, bool $mustBeChecked) {
		if (!$event->hasCircle()
			|| !$event->getCircle()
					  ->hasViewer()) {
			if ($mustBeChecked) {
				throw new GlobalScaleEventException('GSEvent cannot be checked');
			} else {
				return;
			}
		}

		$circle = $event->getCircle();
		$viewer = $circle->getViewer();
		$this->cleanMember($viewer);

		$localCircle = $this->circlesRequest->getCircle(
			$circle->getUniqueId(), $viewer->getUserId(), $viewer->getInstance()
		);

		if (!$this->compareMembers($viewer, $localCircle->getViewer())) {
			throw new GlobalScaleDSyncException('Viewer seems DSync');
		}

		$event->setCircle($localCircle);
	}


	/**
	 * @param Member $member1
	 * @param Member $member2
	 *
	 * @return bool
	 */
	protected function compareMembers(Member $member1, Member $member2) {
		if ($member1->getInstance() === '') {
			$member1->setInstance($this->configService->getLocalCloudId());
		}

		if ($member2->getInstance() === '') {
			$member2->setInstance($this->configService->getLocalCloudId());
		}


		if ($member1->getCircleId() !== $member2->getCircleId()
			|| $member1->getUserId() !== $member2->getUserId()
			|| $member1->getType() <> $member2->getType()
			|| $member1->getLevel() <> $member2->getLevel()
			|| $member1->getStatus() !== $member2->getStatus()
			|| $member1->getInstance() !== $member2->getInstance()) {
			return false;
		}

		return true;
	}


	/**
	 * @param Circle $circle1
	 * @param Circle $circle2
	 *
	 * @return bool
	 */
	protected function compareCircles(Circle $circle1, Circle $circle2): bool {
		if ($circle1->getName() !== $circle2->getName()
			|| $circle1->getDescription() !== $circle2->getDescription()
			|| $circle1->getSettings(true) !== $circle2->getSettings(true)
			|| $circle1->getType() !== $circle2->getType()
			|| $circle1->getUniqueId() !== $circle2->getUniqueId()) {
			return false;
		}

		return true;
	}


	protected function cleanMember(Member $member) {
		if ($member->getInstance() === $this->configService->getLocalCloudId()) {
			$member->setInstance('');
		}
	}

}

