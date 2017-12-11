<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright 2018
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
namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;
use OCA\Circles\Api\v1\Circles;

class ProviderSubjectShared extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param array $params
	 * 
	 * @throws FakeException
	 */
	public function parseSubjectSharedWithCircle(IEvent &$event, array $params) {
		$this->parseSharedEvent(
			$event, $params, null,
			$this->l10n->t('You shared {file} with circle {circle}'),
			$this->l10n->t('{author} shared {file} with the circle {circle}')
		);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectUnsharedWithCircle(IEvent &$event, array $params) {
		$this->parseSharedEvent(
				$event, $params, null,
				$this->l10n->t('You unshared {file} with the circle {circle}'),
				$this->l10n->t('{author} unshared {file} with the circle {circle}')
		);
	}

	/**
	 * general function to generate Circle event.
	 *
	 * @param IEvent $event
	 * @param array $params
	 * @param FederatedLink|null $remote
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseSharedEvent(IEvent &$event, array $params, $remote, $ownEvent, $othersEvent
	) {
		$circle = Circles::infoCircleByName($params['circle']['name']);
		$path = Circles::getViewPath($params['file']['id']);

		$data = [
			'author' => [
				'type'    => 'user',
				'id'      => $params['author']['id'],
				'name'    => $params['author']['name'],
				'_parsed' => $params['author']['name']
			],
			'circle' => [
				'type'    => 'circle',
				'id'      => $circle->getId(),
				'name'    => $circle->getName(),
				'_parsed' => $circle->getName(),
				'link'    => Circles::generateLink($circle->getUniqueId())
			],
			'file' => [
				'type' => 'file',
				'id' => $params['file']['id'],
				'name' => $params['file']['name'],
				'path' => $path,
				'link' => \OC::$server->getURLGenerator ()->linkToRouteAbsolute('files.view.index', array (
					'dir' => ($params['file']['type'] !== 'file') ? dirname($path) : $path) )
			]
		];

		if ($this->isViewerTheAuthor($circle, $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);
			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}
}