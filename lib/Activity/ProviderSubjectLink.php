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


namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\FederatedLink;
use OCP\Activity\IEvent;

class ProviderSubjectLink extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestSent(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_sent') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You sent a request to link {circle} with {remote}'),
			$this->l10n->t('{author} sent a request to link {circle} with {remote}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestReceived(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_received') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote, $this->l10n->t('{remote} requested a link with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestRejected(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_rejected') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('The request to link {circle} with {remote} has been rejected')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestCanceled(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_canceled') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t(
				'The request to link {remote} with {circle} has been canceled remotely'
			)
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestAccepted(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_accepted') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('The request to link {circle} with {remote} has been accepted')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestRemoved(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_removed') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You dismissed the request to link {remote} with {circle}'),
			$this->l10n->t('{author} dismissed the request to link {remote} with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestCanceling(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_canceling') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You canceled the request to link {circle} with {remote}'),
			$this->l10n->t('{author} canceled the request to link {circle} with {remote}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestAccepting(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_accepting') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You accepted the request to link {remote} with {circle}'),
			$this->l10n->t('{author} accepted the request to link {remote} with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkUp(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_up') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('A link between {circle} and {remote} is now up and running')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkDown(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_down') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t(
				'The link between {circle} and {remote} has been shutdown remotely'
			)
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRemove(IEvent $event, DeprecatedCircle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_remove') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You closed the link between {circle} and {remote}'),
			$this->l10n->t('{author} closed the link between {circle} and {remote}')
		);

		throw new FakeException();
	}
}
