<?php

declare(strict_types=1);


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


namespace OCA\Circles\Command;

use Exception;
use JetBrains\PhpStorm\Pure;
use OC\Core\Command\Base;
use OCA\Circles\Model\Debug;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Class CirclesCheck
 *
 * @package OCA\Circles\Command
 */
class CirclesDebug extends Base {
	use TArrayTools;

	public const REFRESH = 50000;

	private DebugService $debugService;
	private ConfigService $configService;

	private TopPanel $topPanel;
	private BottomLeftPanel $bottomLeftPanel;
	private BottomRightPanel $bottomRightPanel;
	private ProgressBar $display;

	/** @var Panel[] $panels */
	private array $panels = [];
	/** @var Debug[] $debugs */
	private array $debugs = [];
	private bool $refresh = false;
	private int $lastId = 0;

	/**
	 * @param DebugService $debugService
	 */
	public function __construct(DebugService $debugService, ConfigService $configService) {
		parent::__construct();

		$this->debugService = $debugService;
		$this->configService = $configService;
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:debug')
			 ->setDescription('Debug!')
			 ->addOption('circle', '', InputOption::VALUE_REQUIRED, 'filter circle', '')
			 ->addOption('history', '', InputOption::VALUE_REQUIRED, 'last history', '50')
			 ->addOption('size', '', InputOption::VALUE_REQUIRED, 'height', '0')
			 ->addOption('ping', '', InputOption::VALUE_NONE, 'ping debug daemon')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'filter instance', '');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('ping')) {
			$this->debugService->info('ping.');

			return 0;
		}

		$this->init();
		$this->initTerminal((int)$input->getOption('size'));
		$this->initPanel($output);
		$this->initHistory((int)$input->getOption('history'));

		while (true) {
			try {
				$this->keyPressed();
			} catch (QuitException $e) {
				break;
			}

			$this->live();
			$this->refresh();

			usleep(self::REFRESH);
		}

//		$this->displayDebugs($debugs);

		return 0;
	}


	private function init(): void {
		stream_set_blocking(STDIN, false);
		readline_callback_handler_install(
			'',
			function () {
			}
		);

		$this->panels[] = $this->topPanel = new TopPanel(
			[
				'currentLine' => 'topPanelCurrentLine',
			]
		);
		$this->panels[] = $this->bottomLeftPanel = new BottomLeftPanel(
			[
				'currentLine' => 'bottomLeftPanelCurrentLine',
			]
		);
		$this->panels[] = $this->bottomRightPanel = new BottomRightPanel(
			[
				'currentLine' => 'bottomRightPanelCurrentLine',
			]
		);
	}


	/**
	 * @param int $height
	 */
	private function initTerminal(int $height = 0): void {
		if ($height === 0) {
			$height = (new Terminal())->getHeight() - 1;
		}

		$this->topPanel->setHeight((int)floor($height / 2));
		$this->bottomLeftPanel->setHeight($height - $this->topPanel->getHeight());
		$this->bottomRightPanel->setHeight($height - $this->topPanel->getHeight());

		$this->topPanel->setMaxLines($this->topPanel->getHeight() - 2);
		$this->bottomLeftPanel->setMaxLines($this->bottomLeftPanel->getHeight() - 7);
		$this->bottomRightPanel->setMaxLines($this->bottomRightPanel->getHeight() - 2);
	}


	/**
	 * @param OutputInterface $output
	 */
	private function initPanel(OutputInterface $output): void {
		$this->display = new ProgressBar($output);
		$this->initPanelMessage();
		$this->display->setOverwrite(true);

		$lines = [];
		$lines[] = '<fg=white>┌─%top%──────────────────────────────────────────────────────</>';

		for ($i = 0; $i < $this->topPanel->getMaxLines(); $i++) {
			$lines[] = '<fg=white>│</>'
					   . $this->incrementString($i, 'topPanelCurrentLine', '%')
					   . $this->incrementstring($i, 'lineT', '%');

			$this->update($this->incrementString($i, 'topPanelCurrentLine'), ' ');
			$this->update($this->incrementString($i, 'lineT'), ' ');
		}

		$lines[] = '<fg=white>└─────</>';
		$lines[] =
			'<fg=white>┌────────────────────────────────────────────┬─────────────%test%─────────────────────────</>';

		for ($i = 0; $i < $this->bottomLeftPanel->getMaxLines(); $i++) {
			$lines[] = '<fg=white>│</>'
					   . $this->incrementString($i, 'bottomLeftPanelCurrentLine', '%') . ' '
					   . $this->incrementString($i, 'lineBL', ':42s%') . ' </>'
					   . '<fg=white>│</> '
					   . $this->incrementString($i, 'lineBR', '%');

			$this->update($this->incrementString($i, 'bottomLeftPanelCurrentLine'), '');
			$this->update($this->incrementString($i, 'lineBL'), '');
			$this->update($this->incrementString($i, 'lineBR'), '');
		}

		$more = ['  Thread', '    Type', 'CircleId', 'Instance', '    Time'];
		for ($j = 0; $j < count($more); $j++) {
			$lines[] = '<fg=white>│ ' . strtolower($more[$j]) . ':</> %curr'
					   . trim($more[$j]) . ':-32s% <fg=white>│</> '
					   . $this->incrementString($i + $j, 'lineBR', '%');
			$this->update($this->incrementString($i + $j, 'lineBR'), '');
		}
		$lines[] = '<fg=white>└────────────────────────────────────────────┘</>';

		$this->setCurr();

//		$this->display->clear();
		$this->display->setFormat(implode("\n", $lines) . "\n");
		$this->display->start();
	}


	/**
	 * @param int $history
	 */
	private function initHistory(int $history): void {
		$debugs = $this->debugService->getHistory(max($history, 1));
		$this->lastId = empty($debugs) ? 0 : $debugs[0]->getId();

		if ($history < 1) {
			return;
		}

		$this->debugs = array_reverse($debugs, false);
		$this->topPanel->setCurrentPage(max(count($this->debugs) - 3, 0));

		$this->refreshHistory();
	}

	/**
	 *
	 */
	private function live(): void {
		$debugs = $this->debugService->getSince($this->lastId);
		if (empty($debugs)) {
			return;
		}

		$this->lastId = $debugs[0]->getId();

		foreach (array_reverse($debugs, false) as $debug) {
			$this->debugs[] = $debug;
		}

		$this->refreshHistory();
	}


	private function refreshHistory(): void {
		$selectableLines = 0;
		for ($i = 0; $i < $this->topPanel->getMaxLines(); $i++) {
			$k = $this->topPanel->getCurrentPage() + $i;
			if (!array_key_exists($k, $this->debugs)) {
				$this->update($this->incrementString($i, 'lineT'), '');
			} else {
				$item = $this->debugs[$k];
				$debug = $item->getDebug();

				$instance = ($this->configService->isLocalInstance($item->getInstance())) ?
					'local' : $item->getInstance();

				$instanceColor = $this->configService->getAppValue('debug_instance.' . $instance);
				if ($instanceColor === '') {
					$instanceColor = 'white';
				}

				$line = '<fg=' . $instanceColor . '>' . $instance . '</> - ';
				$action = $debug->g(DebugService::ACTION);

				preg_match_all('/{((?:[^{}]*|(?R))*)}/x', $action, $match);
				foreach ($match[1] as $entry) {
					$flag = substr($entry, 0, 1);
					if ($flag === '!') {
						$path = substr($entry, 1);
						$color = 'fg=yellow';
					} else if ($flag === '?') {
						$path = substr($entry, 1);
						$color = 'fg=red';
					} else if ($flag === '~') {
						$path = substr($entry, 1);
						$color = 'fg=cyan';
					} else if ($flag === '`') {
						$path = substr($entry, 1);
						$color = 'options=reverse';
					} else {
						$path = $entry;
						$color = 'fg=green';
					}

					$value = $this->get($path, $debug->jsonSerialize());
					if ($value === '') {
						$value = $path;
					}

					$action = str_replace(
						'{' . $entry . '}',
						'<' . $color . '>' . $value . '</>',
						$action
					);
				}

				$selectableLines++;

				$line .= $action;
				$this->update($this->incrementString($i, 'lineT'), $line);
			}
		}

		$this->topPanel->setSelectableLines($selectableLines);
	}

	/**
	 *
	 */
	private function initPanelMessage(): void {
		$this->updates([
						   'test' => '',
					   ]);
	}


	/**
	 * @param array $data
	 */
	private function updates(array $data): void {
		foreach ($data as $k => $v) {
			$this->update($k, (string)$v);
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	private function update(string $key, string $value = ''): void {
		$this->display->setMessage($value, $key);
		$this->forceRefresh();
	}


	/**
	 * @throws QuitException
	 */
	public function keyPressed(): void {
		$n = fread(STDIN, 16);
		if ($n !== '') {
			if (substr($n, 1, 1) === '[') {
				$this->keyActionTopPanel(strtolower(substr($n, 2, 1)));
			}

			$c = str_split($n, 1);
			foreach ($c as $a) {
				$this->onKeyPressed(strtolower($a));
			}
		}
	}

	/**
	 * @param string $key
	 *
	 * @throws QuitException
	 */
	public function onKeyPressed(string $key): void {
		switch ($key) {
			case 'q':
				throw new QuitException();

			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
				$this->keyActionLeftPanel((int)$key);
				break;

			case 'w':
			case 's':
			case 'a':
			case 'd':
				$this->keyActionBottomRightPanel($key);
				break;

			default:
				return;
		}
	}


	/**
	 *
	 */
	private function forceRefresh(): void {
		$this->refresh = true;
	}

	/**
	 *
	 */
	private function refresh(): void {
		if (!$this->isRefreshNeeded()) {
			return;
		}

		$this->display->display();
		$this->cleanRefresh();
	}

	/**
	 * @return bool
	 */
	#[Pure]
	private function isRefreshNeeded(): bool {
		if ($this->refresh) {
			return true;
		}

		foreach ($this->panels as $panel) {
			if ($panel->isModified()) {
				return true;
			}
		}

		return false;
	}

	private function cleanRefresh(): void {
		$this->refresh = false;
		foreach ($this->panels as $panel) {
			$panel->setModified(false);
		}
	}


	/**
	 * @return Debug
	 */
	#[Pure]
	private function getSelectedEntry(): Debug {
		return $this->debugs[$this->topPanel->getCurrentPage() + $this->topPanel->getCurrentLine()];
	}


	/**
	 * @param string $key
	 */
	public function keyActionTopPanel(string $key): void {
		switch ($key) {
			case 'a':
				$done = $this->topPanel->keyUp();
				break;
			case 'b':
				$done = $this->topPanel->keyDown();
				break;
			case 'c':
				$done = $this->topPanel->keyRight();
				$this->refreshHistory();
				break;
			case 'd':
				$done = $this->topPanel->keyLeft();
				$this->refreshHistory();
				break;

			default:
				return;
		}

		if ($done) {
			$this->displayTopPanelCurrentLine();
			$this->displayBottomLeftPanelItems();

			$this->bottomRightPanel->setLines([]);
			$this->bottomRightPanel->setSelectableLines(count($this->bottomRightPanel->getLines()));
			$this->displayBottomRightPanelItems();
		}
	}

	/**
	 * @param string $key
	 */
	public function keyActionBottomRightPanel(string $key): void {
		switch ($key) {
			case 'w':
				$this->bottomRightPanel->keyUp();
				break;
			case 's':
				$this->bottomRightPanel->keyDown();
				break;
//			case 'a':
//				$this->bottomRightPanel->keyLeft();
//				break;
//			case 'd':
//				$this->bottomRightPanel->keyRight();
//				break;

			default:
				return;
		}

		$this->displayBottomRightPanelItems();
	}

	/**
	 *
	 */
	private function displayTopPanelCurrentLine(): void {
		for ($i = 0; $i < $this->topPanel->getMaxLines(); $i++) {
			if ($this->topPanel->getCurrentLine() === $i) {
				$this->update(
					$this->incrementString($i, $this->get('currentLine', $this->topPanel->getInternal())),
//					'<fg=green;options=bold>·</>'
					'<fg=red;options=bold>></>'
				);
			} else {
				$this->update(
					$this->incrementString($i, $this->get('currentLine', $this->topPanel->getInternal())),
					' '
				);
			}
		}
	}


	/**
	 *
	 */
	private function displayBottomLeftPanelItems(): void {
		$this->setCurr($this->getSelectedEntry());
		$debug = $this->getSelectedEntry()->getDebug();


		$references = $debug->getAllReferences();

		$items = [];
		$this->bottomLeftPanel->setSelectableLines(count($references));
		for ($i = 0; $i < $this->bottomLeftPanel->getMaxLines(); $i++) {
			$entry = array_shift($references);
			if (is_null($entry)) {
				$this->update($this->incrementString($i, 'lineBL'), '');
			} else {
				$items[] = $name = $this->get(ReferencedDataStore::KEY_NAME, $entry);

				$type = $this->get(ReferencedDataStore::KEY_TYPE, $entry);
				if ($type === ReferencedDataStore::OBJECT) {
					$path = $this->get(ReferencedDataStore::KEY_CLASS, $entry);
					$type = implode('\\', array_slice(explode('\\', $path), -2));
				}

				$this->update(
					$this->incrementString($i, 'lineBL'),
					($i + 1) . '. ' . $name . ' (' . $type . ')'
				);
			}
		}

		$this->bottomLeftPanel->setItems($items);
		$this->bottomLeftPanel->setCurrentLine(-1);

		$this->displayBottomLeftPanelCurrentLine();
	}


	private function displayBottomLeftPanelCurrentLine(): void {
		for ($i = 0; $i < $this->bottomLeftPanel->getMaxLines(); $i++) {
			if ($this->bottomLeftPanel->getCurrentLine() === $i) {
				$this->update(
					$this->incrementString(
						$i,
						$this->get('currentLine', $this->bottomLeftPanel->getInternal())
					),
					'<options=bold>'
				);
			} else {
				$this->update(
					$this->incrementString(
						$i,
						$this->get('currentLine', $this->bottomLeftPanel->getInternal())
					),
					''
				);
			}
		}
	}


	private function displayBottomRightPanelItems(): void {

		$this->update($this->incrementString(0, 'lineBR'));
//		$this->bottomLeftPanel->setSelectableLines(count($references));

		$lines = $this->bottomRightPanel->getLines();
		for ($i = 0; $i < $this->bottomRightPanel->getMaxLines(); $i++) {
			$c = $i + $this->bottomRightPanel->getCurrentLine();
			if ($this->bottomRightPanel->getSelectableLines() < $c || empty($lines)) {
				$this->update($this->incrementString($i, 'lineBR'), '');
			} else {
				$this->update($this->incrementString($i, 'lineBR'), (string)$lines[$c]);
			}
//				$type = $this->get(ReferencedDataStore::KEY_TYPE, $entry);
//				if ($type === ReferencedDataStore::OBJECT) {
//					$path = $this->get(ReferencedDataStore::KEY_CLASS, $entry);
//					$type = implode('\\', array_slice(explode('\\', $path), -2));
//				}
//
//				$this->update(
//					$this->incrementString($i, 'lineBL'),
//					'circle (' . $type . ')'
//				);
		}

	}


	public function test(int $b) {
		$this->update('top', (string)$b);
	}


	private function keyActionLeftPanel(int $item): void {
		$this->bottomLeftPanel->onKeyItem($item);

		$this->displayBottomLeftPanelCurrentLine();


		$items = $this->bottomLeftPanel->getItems();
		$debug = $this->getSelectedEntry()->getDebug();

		if ($this->bottomLeftPanel->getCurrentLine() < count($items)) {
			$this->bottomRightPanel->setLines(
				explode(
					"\n",
					trim(
						json_encode(
							$debug->gAll()[$items[$this->bottomLeftPanel->getCurrentLine()]],
							JSON_PRETTY_PRINT
						)
					)
				)
			);
		}
		$this->bottomRightPanel->setSelectableLines(count($this->bottomRightPanel->getLines()));
		$this->bottomRightPanel->setCurrentLine(0);

		$this->displayBottomRightPanelItems();
	}


	private function incrementString(int $number, string $prefix = '', string $wrapper = ''): string {
		$str = sprintf('%03d', $number);
		$chars = 'ABCDEFGHIJ';
		$result = '';
		for ($i = 0; $i < 3; $i++) {
			$result .= $chars[(int)$str[$i]];
		}

		if ($wrapper !== '') {
			$prefix = '%' . $prefix;
		}

		return $prefix . $result . $wrapper;
	}

	private function setCurr(?Debug $debug = null) {
		if (is_null($debug)) {
			$this->update('currThread', '');
			$this->update('currType', '');
			$this->update('currCircleId', '');
			$this->update('currInstance', '');
			$this->update('currTime', '');

			return;
		}

		$this->update('currThread', $debug->getThread() ?? '');
		$this->update('currType', $debug->getType() ?? '');
		$this->update('currCircleId', $debug->getCircleId() ?? '');
		$this->update('currInstance', $debug->getInstance() ?? '');
		$this->update('currTime', (string)$debug->getTime() ?? '');
	}

}


class Panel {
	private array $lines = [];
	private int $maxLines = 0;
	private int $selectableLines = 0;
	private int $currentLine = -1;
	private int $height = 0;
	private bool $modified = true;
	private array $internal;
	private int $currentPage;

	public function __construct(array $internal = []) {
		$this->internal = $internal;
	}

	/**
	 * @param array $lines
	 */
	public function setLines(array $lines): void {
		$this->lines = $lines;
	}

	/**
	 * @param int $page
	 *
	 * @return array
	 */
	public function getLines(int $page = -1): array {
		return $this->lines;
	}


	/**
	 * @param int $maxLines
	 */
	public function setMaxLines(int $maxLines): void {
		$this->maxLines = $maxLines;
	}

	/**
	 * @return int
	 */
	public function getMaxLines(): int {
		return $this->maxLines;
	}

	/**
	 * @param int $selectableLines
	 */
	public function setSelectableLines(int $selectableLines): void {
		$this->selectableLines = $selectableLines;
	}

	/**
	 * @return int
	 */
	public function getSelectableLines(): int {
		return $this->selectableLines;
	}


	/**
	 * @param int $currentLine
	 */
	public function setCurrentLine(int $currentLine): void {
		if ($this->currentLine !== $currentLine) {
			$this->setModified(true);
		}

		$this->currentLine = $currentLine;
	}

	/**
	 * @return int
	 */
	public function getCurrentLine(): int {
		return $this->currentLine;
	}


	/**
	 * @param int $currentPage
	 */
	public function setCurrentPage(int $currentPage): void {
		$this->currentPage = $currentPage;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage(): int {
		return $this->currentPage;
	}

	/**
	 * @param int $height
	 */
	public function setHeight(int $height): void {
		$this->height = $height;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * @return array
	 */
	public function getInternal(): array {
		return $this->internal;
	}

	public function keyUp(): bool {
		$curr = $this->getCurrentLine() - 1;
		if ($curr >= 0) {
			$this->setCurrentLine($curr);

			return true;
		}

		return false;
	}

	public function keyDown(): bool {
		$curr = $this->getCurrentLine() + 1;
		if ($curr < $this->getSelectableLines()) {
			$this->setCurrentLine($curr);

			return true;
		}

		return false;
	}

	public function keyLeft(): bool {
		$curr = max($this->getCurrentPage() - $this->getMaxLines(), 0);
		if ($curr !== $this->getCurrentPage()) {
			$this->setCurrentPage($curr);

			return true;
		}

		return false;
	}

	public function keyRight(): bool {
//		$curr = $this->getCurrentLine() + 1;
		$curr = $this->getCurrentPage() + $this->getMaxLines();
		if ($curr !== $this->getCurrentPage()) {
			$this->setCurrentPage($curr);

			return true;
		}

		return false;
	}


	/**
	 * @param bool $modified
	 */
	public function setModified(bool $modified): void {
		$this->modified = $modified;
	}

	/**
	 * @return bool
	 */
	public function isModified(): bool {
		return $this->modified;
	}
}


/**
 *
 */
class TopPanel extends Panel {
}


/**
 *
 */
class BottomLeftPanel extends Panel {
	private array $items = [];

	public function onKeyItem(int $item) {
		if (--$item < $this->getSelectableLines()) {
			$this->setCurrentLine($item);
		}
	}

	/**
	 * @param array $items
	 */
	public function setItems(array $items): void {
		$this->items = $items;
	}

	/**
	 * @return array
	 */
	public function getItems(): array {
		return $this->items;
	}
}

class BottomRightPanel extends Panel {
}

class QuitException extends Exception {
}
