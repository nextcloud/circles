<?php


namespace OCA\Circles\Activity;

use OCA\Circles\AppInfo\Application;
use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {
	/** @var IL10N */
	protected $l10n;

	/** @var IURLGenerator */
	protected $url;

	public function __construct(IL10N $l10n, IURLGenerator $url) {
		$this->l10n = $l10n;
		$this->url = $url;
	}

	/**
	 * @return string Lowercase a-z only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return Application::APP_ID;
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l10n->t('Circles');
	}

	/**
	 * @return int
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 10;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon() {
		return $this->url->getAbsoluteURL(
			$this->url->imagePath(Application::APP_ID, 'circles.svg')
		);
	}

	/**
	 * @param string[] $types
	 *
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types) {
		return $types;
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps() {
		return [Application::APP_ID];
	}
}
