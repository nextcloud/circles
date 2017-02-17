<?php
/**
 * Circles - bring cloud-users closer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

script('circles', 'circles');
script('circles', 'navigation');

style('circles', 'navigation');

?>


<div id="app-navigation">
	<div class="navigation-element">
		<input id="circles_new" type="text" placeholder="<?php p($l->t('New circle')); ?>"/>
	</div>
	<div id="circles_list"></div>
</div>

<div id="app-content">
	<div id="emptycontent">
		<div class="icon-user"></div>
		<h2><?php p($l->t('No team selected')); ?></h2>
	</div>

	<div id="container" class="hidden">
		<label for="addMember" class="hidden-visually"><?php p($l->t('Add team member')); ?></label>
		<input id="addMember" type="text" placeholder="<?php p($l->t('Add team member')); ?>"/>

		<ul class="memberList"></ul>
	</div>

	<div id="loading_members" class="icon-loading hidden"></div>
</div>
