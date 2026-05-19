<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tools\Model;

class TreeNode {
	/** @var self[] */
	private array $children = [];

	private ?self $currentChild = null;
	private bool $displayed = false;
	private bool $splited = false;

	public function __construct(
		private readonly ?TreeNode $parent,
		private readonly SimpleDataStore $item,
	) {
		if ($this->parent !== null) {
			$this->parent->addChild($this);
		}
	}

	public function isRoot(): bool {
		return (is_null($this->parent));
	}

	/**
	 * @return $this
	 */
	public function setChildren(array $children): self {
		$this->children = $children;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function addChild(TreeNode $child): self {
		$this->children[] = $child;

		return $this;
	}

	public function getItem(): SimpleDataStore {
		$this->displayed = true;

		return $this->item;
	}

	public function getParent(): ?TreeNode {
		return $this->parent;
	}

	public function getRoot(): TreeNode {
		if ($this->isRoot()) {
			return $this;
		}

		return $this->getParent()->getRoot();
	}

	/**
	 * @return TreeNode[]
	 */
	public function getPath(): array {
		if ($this->isRoot()) {
			return [$this];
		}

		return array_merge($this->parent->getPath(), [$this]);
	}

	public function getLevel(): int {
		if ($this->isRoot()) {
			return 0;
		}

		return $this->getParent()->getLevel() + 1;
	}

	public function current(): ?TreeNode {
		if (!$this->isDisplayed()) {
			return $this;
		}

		$this->splited = true;
		if ($this->initCurrentChild()) {
			$next = $this->getCurrentChild()->current();
			if (!is_null($next)) {
				return $next;
			}
		}

		return $this->next();
	}

	private function next(): ?TreeNode {
		$this->currentChild = array_shift($this->children);

		return $this->currentChild;
	}

	public function haveNext(): bool {
		return !empty($this->children);
	}

	private function initCurrentChild(): bool {
		if (is_null($this->currentChild)) {
			if (!$this->haveNext()) {
				return false;
			}
			$this->next();
		}

		return true;
	}

	private function getCurrentChild(): ?TreeNode {
		return $this->currentChild;
	}

	private function isDisplayed(): bool {
		return $this->displayed;
	}

	public function isSplited(): bool {
		return $this->splited;
	}
}
