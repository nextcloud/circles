<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
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

$navigationEntry = function () {
	return [
		'id' => 'teams',
		'order' => 10,
		'name' => \OC::$server->getL10N('teams')->t('Teams'),
		'href' => \OC::$server->getURLGenerator()->linkToRoute('teams.Teams.show'),
		'icon' => \OC::$server->getURLGenerator()->imagePath('teams', 'app.svg'),
	];
};
\OC::$server->getNavigationManager()->add($navigationEntry);