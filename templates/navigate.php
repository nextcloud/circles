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

script('circles', 'notyf');
script('circles', 'circles');
script('circles', 'navigation');

style('circles', 'notyf');
style('circles', 'navigation');

?>


<div id="app-navigation">
	<div class="navigation-element" style="height: 250px;">
		<input id="circles_new_name" type="text"
			   placeholder="<?php p($l->t('Create a new circle')); ?>"/>
		<select id="circles_new_type" style="display: none;">

			<?php

			// Personal Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PERSONAL]) {
				print_unescaped(
					sprintf(
						'<option value="%d">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_PERSONAL,
						"Create a Personal Circle"
					)
				);
			}

			// Hidden Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_HIDDEN]) {
				print_unescaped(
					sprintf(
						'<option value="%d">%s</option>', \OCA\Circles\Model\Circle::CIRCLES_HIDDEN,
						"Create an Hidden Circle"
					)
				);
			}

			// Private Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PRIVATE]) {
				print_unescaped(
					sprintf(
						'<option value="%d">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_PRIVATE,
						"Create a Private Circle"
					)
				);
			}

			// Public Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PUBLIC]) {
				print_unescaped(
					sprintf(
						'<option value="%d">%s</option>', \OCA\Circles\Model\Circle::CIRCLES_PUBLIC,
						"Create a Public Circle"
					)
				);
			}
			?>

		</select>
		<input id="circles_new_submit" type="submit" value="Creation" style="display: none;"/>

		<div id="circles_new_type_definition" style="display: none;">
			<div id="circles_new_type_1"><b>A Personal Circle is a list of users known only
					to
					yourself.</b><br/>Use this if you want to send messsage or share thing
				repeatedly to the same group of people.
			</div>
			<div id="circles_new_type_2"><b>An Hidden Circle is an open group that can be
					protected by
					a password.</b><br/>Select this circle to create a community not displayed as a
				Public Circle.
			</div>
			<div id="circles_new_type_4"><b>A Private Circle require invitation or a
					confirmation
					from an admin.</b> <br/>This is the best circle if you are looking for privacy
				when sharing your files or your ideas.
			</div>
			<div id="circles_new_type_8"><b>A Public Circle is an open group visible to anyone
					that dare to join. </b><br/>Your circle will be visible to everyone and everyone
				will be able to join the circle.
			</div>
		</div>
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
