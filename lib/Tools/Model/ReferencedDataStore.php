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


namespace OCA\Circles\Tools\Model;

use JsonSerializable;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;
use ReflectionClass;
use ReflectionException;

class ReferencedDataStore implements IDeserializable, JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	public const STRING = 'string';
	public const INTEGER = 'integer';
	public const BOOLEAN = 'boolean';
	public const ARRAY = 'array';
	public const OBJECT = 'object';

	public const KEY_NAME = 'name';
	public const KEY_TYPE = 'type';
	public const KEY_CLASS = 'class';

	public const _THIS = '__this';
	public const _REFERENCE = '__reference';

	private array $ref = [];
	private array $data = [];
	private string $lock = IReferencedObject::class;

	public function __construct(array $data = []) {
		$this->data = $data;
	}


	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return ReferencedDataStore
	 */
	public function s(string $key, string $value): self {
		$this->data[$key] = $value;
		$this->ref($key, self::STRING);

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidItemException
	 */
	public function g(string $key): string {
		$this->confirmRef($key, self::STRING);

		return $this->get($key, $this->data);
	}


	/**
	 * @param string $key
	 *
	 * @return ReferencedDataStore
	 */
	public function u(string $key): self {
		if ($this->hasKey($key)) {
			unset($this->data[$key]);
		}

		return $this;
	}


	/**
	 * @param string $key
	 * @param int $value
	 *
	 * @return ReferencedDataStore
	 */
	public function sInt(string $key, int $value): self {
		$this->data[$key] = $value;
		$this->ref($key, self::INTEGER);

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 * @throws InvalidItemException
	 */
	public function gInt(string $key): int {
		$this->confirmRef($key, self::INTEGER);

		return $this->getInt($key, $this->data);
	}


	/**
	 * @param string $key
	 * @param bool $value
	 *
	 * @return ReferencedDataStore
	 */
	public function sBool(string $key, bool $value): self {
		$this->data[$key] = $value;
		$this->ref($key, self::BOOLEAN);

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 * @throws InvalidItemException
	 */
	public function gBool(string $key): bool {
		$this->confirmRef($key, self::BOOLEAN);

		return $this->getBool($key, $this->data);
	}


	/**
	 * @param string $key
	 * @param array $value
	 *
	 * @return ReferencedDataStore
	 */
	public function sArray(string $key, array $value): self {
		$this->data[$key] = $value;
		$this->ref($key, self::ARRAY);

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 * @throws InvalidItemException
	 */
	public function gArray(string $key): array {
		$this->confirmRef($key, self::ARRAY);

		return $this->getArray($key, $this->data);
	}


	/**
	 * @param string $key
	 * @param JsonSerializable $value
	 *
	 * @return ReferencedDataStore
	 */
	public function sObj(string $key, JsonSerializable $value): self {
		$this->data[$key] = $value;
		$this->ref($key, self::OBJECT, $value);

		return $this;
	}


	/**
	 * @param string $key
	 * @param string $class
	 *
	 * @return JsonSerializable[]
	 */
//	public function gObjs(string $key, string $class = ''): array {
//		$list = $this->gArray($key);
//		$result = [];
//		foreach ($list as $item) {
//			$data = new SimpleDataStore([$key => $item]);
//			$result[] = $data->gObj($key, $class);
//		}
//
//		return array_filter($result);
//	}


	/**
	 * @param string $key
	 *
	 * @return null|JsonSerializable
	 * @throws InvalidItemException
	 */
	public function gObj(string $key): ?IDeserializable {
		$this->confirmRef($key, self::OBJECT);
		$class = $this->getRef($key, self::KEY_CLASS);

		$item = $this->data[$key];
		if ($item instanceof IDeserializable) {
			return $item;
		}

		try {
			$reflection = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new InvalidItemException('reflection issue with ' . $class);
		}

		if (!$reflection->implementsInterface(IDeserializable::class)) {
			throw new InvalidItemException('object does not implements IDeserializable');
		}

		if ($this->locked() !== '' && !$reflection->implementsInterface($this->locked())) {
			throw new InvalidItemException('model is locked');
		}

		return $this->deserialize($item, $class);
	}


	/**
	 * @param string $key
	 *
	 * @return mixed
	 * @throws ItemNotFoundException
	 */
//	public function gItem(string $key) {
//		if (!array_key_exists($key, $this->data)) {
//			throw new ItemNotFoundException();
//		}
//
//		return $this->data[$key];
//	}


	/**
	 * @param string $k
	 * @param mixed $obj
	 *
	 * @return ReferencedDataStore
	 * @throws InvalidItemException
	 */
	public function sMixed(string $k, mixed $obj): self {
		if ($obj instanceof JsonSerializable) {
			return $this->sObj($k, $obj);
		}
		if (is_array($obj)) {
			return $this->sArray($k, $obj);
		}
		if (is_integer($obj)) {
			return $this->sInt($k, $obj);
		}
		if (is_string($obj)) {
			return $this->s($k, $obj);
		}
		if (is_bool($obj)) {
			return $this->sBool($k, $obj);
		}

		throw new InvalidItemException();
	}


	/**
	 * @param string $json
	 *
	 * @return $this
	 * @throws InvalidItemException
	 */
	public function json(string $json): self {
		$this->import(json_decode($json, true));

		return $this;
	}


	public function keys(): array {
		return array_keys($this->data);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasKey(string $key): bool {
		return (array_key_exists($key, $this->data));
	}


	/**
	 * @param array $keys
	 *
	 * @return bool
	 */
	public function hasKeys(array $keys): bool {
		foreach ($keys as $key) {
			if (!$this->hasKey($key)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param string $lock
	 *
	 * @return $this
	 */
	public function lock(string $lock): self {
		$this->lock = $lock;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function unlock(): self {
		$this->lock = '';

		return $this;
	}

	/**
	 * @return string
	 */
	public function locked(): string {
		return $this->lock;
	}


	/**
	 * @return array
	 */
	public function gAll(): array {
		return $this->data;
	}


	/**
	 * @param string $key
	 * @param string $type
	 * @param JsonSerializable|null $object
	 *
	 * @return $this
	 */
	private function ref(string $key, string $type, ?JsonSerializable $object = null): self {
		$ref = [self::KEY_TYPE => $type];

		if ($key !== self::_THIS) {
			$ref[self::KEY_NAME] = $key;
		}

		if (!is_null($object)) {
			$ref[self::KEY_CLASS] = get_class($object);
		}

		$this->ref[$key] = $ref;

		return $this;
	}

	/**
	 * @param string $key
	 * @param string $ref
	 *
	 * @return string
	 */
	private function getRef(string $key, string $ref): string {
		return $this->get($key . '.' . $ref, $this->ref);
	}

	/**
	 * @param string $key
	 * @param string $type
	 *
	 * @throws InvalidItemException
	 */
	private function confirmRef(string $key, string $type): void {
		if ($this->getRef($key, self::KEY_TYPE) === $type) {
			return;
		}

		throw new InvalidItemException();
	}

	public function getType(string $key): string {
		return $this->getRef($key, self::KEY_TYPE);
	}


	/**
	 * @return array
	 */
	public function getAllReferences(): array {
		$ref = $this->ref;
		unset($ref[self::_THIS]);

		return $ref;
	}

	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		$this->ref = $this->getArray(self::_REFERENCE, $data);

		if ($this->getRef(self::_THIS, self::KEY_TYPE) !== self::OBJECT
			|| $this->getRef(self::_THIS, self::KEY_CLASS) !== get_class($this)) {
			throw new InvalidItemException();
		}

		unset($data[self::_REFERENCE]);
		$this->data = $data;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$this->ref(self::_THIS, self::OBJECT, $this);

		return array_merge(
			[
				'__reference' => $this->ref,
			],
			$this->data
		);
	}
}
