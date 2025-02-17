<?php

/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019-2022 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OCP\Share\IAttributes;

class ShareAttributes implements IAttributes {
	public function __construct()
 {
 }

	/**
	 * @inheritdoc
	 */
	public function setAttribute(string $scope, string $key, mixed $value): IAttributes
 {
 }

	/**
	 * @inheritdoc
	 */
	public function getAttribute(string $scope, string $key): mixed
 {
 }

	/**
	 * @inheritdoc
	 */
	public function toArray(): array
 {
 }
}
