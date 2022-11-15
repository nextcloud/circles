<?php
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


namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCP\Activity\IEvent;

class ProviderSubjectGroup extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupLink(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($event->getSubject() !== 'group_link') {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$this->l10n->t('You linked {group} to {circle}'),
			$this->l10n->t('{group} has been linked to {circle} by {author}')
		);
		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupUnlink(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($event->getSubject() !== 'group_unlink') {
			return;
		}
		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$this->l10n->t('You unlinked {group} from {circle}'),
			$this->l10n->t('{group} has been unlinked from {circle} by {author}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupLevel(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($event->getSubject() !== 'group_level') {
			return;
		}

		$l = $this->l10n;

		$level = [$l->t($group->getLevelString())];
		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$l->t('You changed the level of the linked group {group} in {circle} to %1$s', $level),
			$l->t('{author} changed the level of the linked group {group} in {circle} to %1$s', $level)
		);

		throw new FakeException();
	}
}
