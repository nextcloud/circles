<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\UnifiedSearch;

use OCP\Search\SearchResultEntry;

/**
 * Class UnifiedSearchResult
 *
 * @package OCA\Circles\UnifiedSearch
 */
class UnifiedSearchResult extends SearchResultEntry {
	/**
	 * UnifiedSearchResult constructor.
	 *
	 * @param string $thumbnailUrl
	 * @param string $title
	 * @param string $subtitle
	 * @param string $resourceUrl
	 * @param string $icon
	 * @param bool $rounded
	 */
	public function __construct(
		string $thumbnailUrl = '',
		string $title = '',
		string $subtitle = '',
		string $resourceUrl = '',
		string $icon = '',
		bool $rounded = false,
	) {
		parent::__construct($thumbnailUrl, $title, $subtitle, $resourceUrl, $icon, $rounded);
	}
}
