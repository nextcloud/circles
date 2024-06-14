<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\WellKnownLinkNotFoundException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\NCWebfinger;
use OCA\Circles\Tools\Model\NCWellKnownLink;
use OCA\Circles\Tools\Model\SimpleDataStore;

trait TNCWellKnown {
	use TNCRequest;

	public static $WEBFINGER = '/.well-known/webfinger';


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return SimpleDataStore
	 * @throws RequestNetworkException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function getResourceData(string $host, string $subject, string $rel): SimpleDataStore {
		$link = $this->getLink($host, $subject, $rel);

		$request = new NCRequest('');
		$request->basedOnUrl($link->getHref());
		$request->addHeader('Accept', $link->getType());
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		$data = $this->retrieveJson($request);

		return new SimpleDataStore($data);
	}


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return NCWellKnownLink
	 * @throws RequestNetworkException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function getLink(string $host, string $subject, string $rel): NCWellKnownLink {
		return $this->extractLink($rel, $this->getWebfinger($host, $subject));
	}


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return NCWebfinger
	 * @throws RequestNetworkException
	 */
	public function getWebfinger(string $host, string $subject, string $rel = ''): NCWebfinger {
		$request = new NCRequest(self::$WEBFINGER);
		$request->setHost($host);
		$request->setProtocols(['https', 'http']);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);

		$request->addParam('resource', $subject);
		if ($rel !== '') {
			$request->addParam('rel', $rel);
		}

		$result = $this->retrieveJson($request);

		return new NCWebfinger($result);
	}


	/**
	 * @param string $rel
	 * @param NCWebfinger $webfinger
	 *
	 * @return NCWellKnownLink
	 * @throws WellKnownLinkNotFoundException
	 */
	public function extractLink(string $rel, NCWebfinger $webfinger): NCWellKnownLink {
		foreach ($webfinger->getLinks() as $link) {
			if ($link->getRel() === $rel) {
				return $link;
			}
		}

		throw new WellKnownLinkNotFoundException();
	}
}
