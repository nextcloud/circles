<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

/**
 * Interface IFederatedItemSharedItem
 *
 * @package OCA\Circles
 */
interface IFederatedItemSharedItem {
	// meaning that the verify() will be run on the instance that locked the item, not on the main instance of the Circle.
}
