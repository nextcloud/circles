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


namespace OCA\Circles\Model;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class DavCard
 *
 * @package OCA\Circles\Model
 */
class DavCard implements JsonSerializable {


	use TArrayTools;


	/** @var int */
	private $addressBookId = 0;

	/** @var string */
	private $cardUri = '';

	/** @var string */
	private $contactId = '';

	/** @var string */
	private $fn = '';

	/** @var array */
	private $emails = [];

	/** @var array */
	private $groups = [];

	/** @var Circle[] */
	private $circles = [];


	public function __construct() {
	}


	/**
	 * @return int
	 */
	public function getAddressBookId(): int {
		return $this->addressBookId;
	}

	/**
	 * @param int $addressBookId
	 *
	 * @return DavCard
	 */
	public function setAddressBookId(int $addressBookId): self {
		$this->addressBookId = $addressBookId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCardUri(): string {
		return $this->cardUri;
	}

	/**
	 * @param string $cardUri
	 *
	 * @return DavCard
	 */
	public function setCardUri(string $cardUri): self {
		$this->cardUri = $cardUri;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getContactId(): string {
		return $this->contactId;
	}

	/**
	 * @param string $contactId
	 *
	 * @return DavCard
	 */
	public function setContactId(string $contactId): self {
		$this->contactId = $contactId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getFn(): string {
		return $this->fn;
	}

	/**
	 * @param string $fn
	 *
	 * @return DavCard
	 */
	public function setFn(string $fn): self {
		$this->fn = $fn;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getEmails(): array {
		return $this->emails;
	}

	/**
	 * @param array $emails
	 *
	 * @return DavCard
	 */
	public function setEmails(array $emails): self {
		$this->emails = $emails;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getGroups(): array {
		return $this->groups;
	}

	/**
	 * @param array $groups
	 *
	 * @return DavCard
	 */
	public function setGroups(array $groups): self {
		$this->groups = $groups;

		return $this;
	}


	/**
	 * @return Circle[]
	 */
	public function getCircles(): array {
		return $this->circles;
	}

	/**
	 * @param array $circles
	 *
	 * @return DavCard
	 */
	public function setCircles(array $circles): self {
		$this->circles = $circles;

		return $this;
	}

	/**
	 * @param Circle $circle
	 *
	 * @return $this
	 */
	public function addCircle(Circle $circle): self {
		$this->circles[] = $circle;

		return $this;
	}


	/**
	 * @param array $data
	 */
	public function import(array $data) {
		$this->setAddressBookId($this->get('addressBookId', $data));
		$this->setCardUri($this->get('cardUri', $data));
		$this->setContactId($this->get('uid', $data));
		$this->setFn($this->get('fn', $data));
		$this->setEmails($this->getArray('emails', $data));
		$this->setGroups($this->getArray('groups', $data));
	}


	/**
	 * @param string $dav
	 */
	public function importFromDav(string $dav) {
		$data = $this->parseDav($dav);

		$this->setContactId($this->get('UID', $data));
		$this->setFn($this->get('FN', $data));
		$this->setEmails($this->getArray('EMAILS', $data));
		$this->setGroups($this->getArray('CATEGORIES', $data));
	}


	/**
	 * get essential data from the dav content
	 * (also don't think we need regex)
	 *
	 * @param string $dav
	 *
	 * @return array
	 */
	private function parseDav(string $dav): array {
		$result = [
			'UID'        => '',
			'FN'         => '',
			'EMAILS'     => [],
			'CATEGORIES' => []
		];

		$data = preg_split('/\R/', $dav);
		foreach ($data as $entry) {
			if (trim($entry) === '' || strpos($entry, ':') === false) {
				continue;
			}
			list($k, $v) = explode(':', $entry, 2);

			$k = strtoupper($k);
			if (strpos($entry, ';') !== false) {
				list($k) = explode(';', $entry, 2);
			}

			switch ($k) {
				case 'UID':
				case 'FN':
					$result[$k] = $v;
					break;

				case 'EMAIL':
					$result['EMAILS'][] = $v;
					break;

				case 'CATEGORIES':
					if (strpos($v, ',') === false) {
						$result['CATEGORIES'] = $v;
					} else {
						$result['CATEGORIES'] = explode(',', $v);
					}
					break;
			}
		}

		return $result;

	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'addressBookId' => $this->getAddressBookId(),
			'cardUri'       => $this->getCardUri(),
			'uid'           => $this->getContactId(),
			'fn'            => $this->getFn(),
			'emails'        => $this->getEmails(),
			'groups'        => $this->getGroups()
		];
	}

}
