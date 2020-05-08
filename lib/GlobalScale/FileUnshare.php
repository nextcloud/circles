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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use Exception;
use OC\Share20\Share;
use OCA\Circles\Exceptions\BroadcasterIsNotCompatibleException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;


/**
 * Class FileUnshare
 *
 * @package OCA\Circles\GlobalScale
 */
class FileUnshare extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 *
	 * @throws GSStatusException
	 * @throws TokenDoesNotExistException
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
		// TODO: might be a bad idea, all process of the sharing should be managed from here.
		// Even if we are not in a GS setup.
		// The reason is that if a mail needs to be send, all mail address associated to the circle needs to be retrieved
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}
		\OC::$server->getLogger()->log(3, '### 0');
		// if event/file is local, we generate a federate share for the same circle on other instances
		if ($event->getSource() === $this->configService->getLocalCloudId()) {
			$circle = $event->getCircle();

			\OC::$server->getLogger()->log(3, '### 1');

			try {
				$share = $this->getShareFromData($event->getData());
			} catch (Exception $e) {
				return;
			}

			\OC::$server->getLogger()->log(3, '### 2');


			$this->miscService->log('### ' . json_encode($event->getData()));

			try {
				$node = $share->getNode();
				$filename = $node->getName();
			} catch (NotFoundException $e) {
				$filename = '/testTest.md';
			}

			$event->getData()
				  ->s('gs_federated', $share->getToken())
				  ->s('gs_filename', '/' . $filename);
		}

	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws GSStatusException
	 */
	public function manage(GSEvent $event): void {
		// TODO: might be a bad idea, all process of the sharing should be managed from here.
		// Even if we are not in a GS setup.
		// The reason is that if a mail needs to be send, all mail address associated to the circle needs to be retrieved
		if (!$this->configService->getGSStatus(ConfigService::GS_ENABLED)) {
			return;
		}

		// if event is not local, we create a federated file to the right instance of Nextcloud, using the right token
		if ($event->getSource() !== $this->configService->getLocalCloudId()) {
			try {
				$share = $this->getShareFromData($event->getData());
			} catch (Exception $e) {
				return;
			}

			$data = $event->getData();
			$token = $data->g('gs_federated');
			$filename = $data->g('gs_filename');

			$gsShare = new GSShare($share->getSharedWith(), $token);
			$gsShare->setOwner($share->getShareOwner());
			$gsShare->setInstance($event->getSource());
			$gsShare->setParent(-1);
			$gsShare->setMountPoint($filename);

			$this->gsSharesRequest->create($gsShare);
		}

	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}


	/**
	 * @param GSEvent $event
	 *
	 * @throws BroadcasterIsNotCompatibleException
	 * @throws GSStatusException
	 */
	private function generateFederatedShare(GSEvent $event) {
		$data = $event->getData();
		$frame = SharingFrame::fromJSON(json_encode($data->gAll()));

		try {
			$broadcaster = \OC::$server->query((string)$frame->getHeader('broadcast'));
			if (!($broadcaster instanceof IBroadcaster)) {
				throw new BroadcasterIsNotCompatibleException();
			}

			$frameCircle = $frame->getCircle();
			$circle = $this->circlesRequest->forceGetCircle($frameCircle->getUniqueId());
		} catch (QueryException | CircleDoesNotExistException $e) {
			return;
		}

		$this->feedBroadcaster($broadcaster, $frame, $circle);
	}


	/**
	 * @param IBroadcaster $broadcaster
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 */
	private function feedBroadcaster(IBroadcaster $broadcaster, SharingFrame $frame, Circle $circle) {
		$broadcaster->init();

		if ($circle->getType() !== Circle::CIRCLES_PERSONAL) {
			$broadcaster->createShareToCircle($frame, $circle);
		}

		$members =
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), Member::LEVEL_MEMBER, 0,true);
		foreach ($members AS $member) {
			$this->parseMember($member);

			if ($member->isBroadcasting()) {
				$broadcaster->createShareToMember($frame, $member);
			}

			if ($member->getInstance() !== '') {
				$this->miscService->log('#### GENERATE FEDERATED CIRCLES SHARE ' . $member->getInstance());
			}
		}
	}


	/**
	 * @param Member $member
	 */
	private function parseMember(Member &$member) {
		$this->parseMemberFromContact($member);
	}


	/**
	 * on Type Contact, we convert the type to MAIL and retrieve the first mail of the list.
	 * If no email, we set the member as not broadcasting.
	 *
	 * @param Member $member
	 */
	private function parseMemberFromContact(Member &$member) {
		if ($member->getType() !== Member::TYPE_CONTACT) {
			return;
		}

		$contact = MiscService::getContactData($member->getUserId());
		if (!key_exists('EMAIL', $contact)) {
			$member->broadcasting(false);

			return;
		}

		$member->setType(Member::TYPE_MAIL);
		$member->setUserId(array_shift($contact['EMAIL']));
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return IShare
	 * @throws ShareNotFound
	 * @throws IllegalIDChangeException
	 */
	private function getShareFromData(SimpleDataStore $data) {
		$frame = SharingFrame::fromArray($data->gArray('frame'));
		$payload = $frame->getPayload();
		if (!key_exists('share', $payload)) {
			throw new ShareNotFound();
		}

		return $this->generateShare($payload['share']);
	}


	/**
	 * recreate the share from the JSON payload.
	 *
	 * @param array $data
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 */
	private function generateShare($data): IShare {
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId($data['id']);
		$share->setSharedBy($data['sharedBy']);
		$share->setSharedWith($data['sharedWith']);
		$share->setNodeId($data['nodeId']);
		$share->setShareOwner($data['shareOwner']);
		$share->setPermissions($data['permissions']);
		$share->setToken($data['token']);
		$share->setPassword($data['password']);

		return $share;
	}


}
