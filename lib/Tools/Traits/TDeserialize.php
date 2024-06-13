<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use JsonSerializable;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;

trait TDeserialize {
	/**
	 * @param JsonSerializable $model
	 *
	 * @return array
	 */
	public function serialize(JsonSerializable $model): array {
		return json_decode(json_encode($model), true);
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function serializeArray(array $data): array {
		return json_decode(json_encode($data), true);
	}


	/**
	 * @param array $data
	 * @param string $class
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function deserialize(array $data, string $class): IDeserializable {
		/** @var IDeserializable $item */
		$item = new $class;
		$item->import($data);

		return $item;
	}


	/**
	 * @param array $data
	 * @param string $class
	 * @param bool $associative
	 *
	 * @return IDeserializable[]
	 */
	public function deserializeArray(array $data, string $class, bool $associative = false): array {
		$arr = [];
		foreach ($data as $key => $entry) {
			if (!is_array($entry)) {
				continue;
			}

			try {
				if ($associative) {
					$arr[$key] = $this->deserialize($entry, $class);
				} else {
					$arr[] = $this->deserialize($entry, $class);
				}
			} catch (InvalidItemException $e) {
			}
		}

		return $arr;
	}


	/**
	 * @param string $json
	 * @param string $class
	 *
	 * @return IDeserializable[]
	 * @throws InvalidItemException
	 */
	public function deserializeList(string $json, string $class): array {
		$arr = [];
		$data = json_decode($json, true);
		if (!is_array($data)) {
			return $arr;
		}

		foreach ($data as $entry) {
			$arr[] = $this->deserialize($entry, $class);
		}

		return $arr;
	}



	/**
	 * @param string $json
	 * @param string $class
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function deserializeJson(string $json, string $class): IDeserializable {
		$data = json_decode($json, true);

		return $this->deserialize($data, $class);
	}
}
