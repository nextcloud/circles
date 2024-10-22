<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use OCA\Circles\Tools\Model\TreeNode;
use Symfony\Component\Console\Output\ConsoleOutput;

trait TConsoleTree {
	/**
	 * @param TreeNode $root
	 * @param callable $method
	 * @param array $config
	 */
	public function drawTree(
		TreeNode $root,
		callable $method,
		array $config = [
			'height' => 1,
			'node-spacing' => 0,
			'item-spacing' => 0,
		],
	): void {
		$config = array_merge(
			[
				'height' => 1,
				'node-spacing' => 0,
				'item-spacing' => 0
			], $config
		);

		$output = new ConsoleOutput();
		$prec = 0;

		while (true) {
			$node = $root->current();
			if ($node === null) {
				return;
			}

			$path = $node->getPath();
			array_pop($path);

			$line = $empty = $spacing = '';
			$p = 0;
			foreach ($path as $k => $i) {
				$line .= ' ';
				$empty .= ' ';
				if ($k === array_key_last($path)) {
					if ($i->haveNext()) {
						$line .= '├';
						$empty .= '│';
					} else {
						$line .= '└';
						$empty .= ' ';
					}
					$line .= '── ';
					$empty .= '   ';
				} else {
					if ($i->haveNext()) {
						$line .= '│';
						$empty .= '│';
					} else {
						$line .= ' ';
						$empty .= ' ';
					}
					$line .= '   ';
					$empty .= '   ';
				}
				$p++;
			}

			if ($p < $prec) {
				for ($i = 0; $i < $config['node-spacing']; $i++) {
					$spacing = substr($empty, 0, -3);
					if (substr($spacing, -1) === ' ') {
						$spacing = substr($spacing, 0, -1) . '│';
					}
					$output->writeln($spacing);
				}
			}

			$prec = $p;

			for ($i = 1; $i <= $config['height']; $i++) {
				$draw = $method($node->getItem(), $i);
				if ($draw === '') {
					continue;
				}
				if ($i === 1) {
					$output->write($line);
				} else {
					$output->write($empty);
				}
				$output->writeln($draw);
			}

			if ($node->haveNext()) {
				$empty .= ' │';
			}

			if (!$node->isSplited() && $node->haveNext()) {
				for ($i = 0; $i < $config['node-spacing']; $i++) {
					$output->writeln($empty);
				}
			}

			for ($i = 0; $i < $config['item-spacing']; $i++) {
				$output->writeln($empty);
			}
		}
	}
}
