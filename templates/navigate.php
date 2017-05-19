<?php
/**
 * Circles - Bring cloud-users closer together.
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

script('circles', 'vendor/notyf');
style('circles', 'notyf');

script('circles', 'circles.app.elements');
script('circles', 'circles.app.actions');
script('circles', 'circles.app.navigation');
script('circles', 'circles.app');

style('circles', 'navigation');

?>


<div id="app-navigation" class="noborder" style="position: relative">
	<div class="navigation-element" style="height: 100%; padding-top: 15px">
		<input id="circles_new_name" type="text"
			   placeholder="<?php p($l->t('Create a new circle')); ?>"/>
		<select id="circles_new_type" style="display: none;" class="select_none">
			<option value="" style="font-style: italic">&nbsp;&nbsp;&nbsp;&nbsp;<?php p(
					$l->t("Select a type of circle")
				); ?></option>
			<?php

			// Personal Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PERSONAL]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>', 'personal',
						$l->t("Create a personal circle")
					)
				);
			}

			// Hidden Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_HIDDEN]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>', 'hidden',
						$l->t("Create an hidden circle")
					)
				);
			}

			// Private Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PRIVATE]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>', 'private',
						$l->t("Create a private circle")
					)
				);
			}

			// Public Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PUBLIC]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>', 'public',
						$l->t("Create a public circle")
					)
				);
			}
			?>

		</select>
		<input id="circles_new_submit" type="submit" value="Creation" style="display: none;"/>

		<div id="circles_new_type_definition" style="display: none;">
			<div id="circles_new_type_personal"><b>
					<?php p(
						$l->t(
							"A personal circle is a list of users known only to the owner."
						)
					); ?>
				</b><br/>
				<?php p(
					$l->t(
						"This is the right option if you want to do recurrent sharing with the same group."
					)
				); ?>
			</div>
			<div id="circles_new_type_hidden"><b>
					<?php p(
						$l->t(
							"An hidden circle is an open group that can be protected by a password."
						)
					); ?></b><br/><?php p(
					$l->t(
						"Users won't be able to find this circle using the Nextcloud search engine."
					)
				); ?>
			</div>
			<div id="circles_new_type_private"><b><?php p(
						$l->t(
							"A private circle requires invitation or confirmation by an admin."
						)
					); ?>
				</b><br/><?php p(
					$l->t(
						"This is the right circle if you are looking for privacy when sharing your files or ideas."
					)
				); ?>
			</div>
			<div id="circles_new_type_public"><b><?php p(
						$l->t(
							"A public circle is an open group visible to anyone willing to join."
						)
					); ?></b><br/><?php p(
					$l->t(
						"Everyone will be able to see and join your circle."
					)
				); ?>
			</div>
		</div>
	</div>
	<div id="circles_list">

		<?php
		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PERSONAL]) {
			print_unescaped('<div circle-type="personal">' . $l->t('personal circles') . '</div>');
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_HIDDEN]) {
			print_unescaped('<div circle-type="hidden">' . $l->t('hidden circles') . '</div>');
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PRIVATE]) {
			print_unescaped('<div circle-type="private">' . $l->t('private circles') . '</div>');
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PUBLIC]) {
			print_unescaped('<div circle-type="public">' . $l->t('public circles') . '</div>');
		}
		?>

		<div circle-type="all"><?php p($l->t('All circles')); ?></div>
	</div>

</div>

<div id="app-navigation" class="circles" style="display: none;">
	<input id="circles_search" type="text"
		   placeholder="<?php p($l->t('Search circles')); ?>"/>
	<select id="circles_filters">
		<option value="0"><?php p($l->t('No filter')); ?></option>
		<option value="1"><?php p($l->t('Circles you belong to')); ?></option>
		<option value="9"><?php p($l->t('Circles you own')); ?></option>
	</select>

</div>

<script id="tmpl_circle" type="text/template">
	<div class="title">%title%</div>
	<div class="type"><b>%type%</b> (%status%, %level_string%)</div>
	<div class="owner"><b><?php p($l->t('Owner')); ?>:</b> %owner%</div>
</script>

<div id="emptycontent">
	<div class="icon-circles"></div>
	<h2><?php p($l->t('No circle selected')); ?></h2>
</div>

<div id="app-content" style="position: relative">

	<div id="mainui">

		<div id="circle_details">
			<div class="lightenbg"></div>
			<div id="name"></div>
			<div id="type"></div>


			<div id="joincircle_invit"><?php p(
					$l->t("Pending invitation to join this circle")
				); ?></div>
			<div id="joincircle_interact">
				<input id="joincircle_acceptinvit" type="submit"
					   value="<?php p($l->t('Accept the invitation')); ?>"/>
				<input id="joincircle_rejectinvit" type="submit"
					   value="<?php p($l->t('Decline the invitation')); ?>"/>
			</div>

			<div id="joincircle_request">You have a pending request to join this circle</div>
			<input id="destroycircle" type="submit"
				   value="<?php p($l->t('Delete this circle')); ?>"/>
			<input id="joincircle" type="submit"
				   value="<?php p($l->t('Join this circle')); ?>"/>
			<input id="leavecircle" type="submit"
				   value="<?php p($l->t('Leave this circle')); ?>"/>
			<input id="addmember" type="text"
				   placeholder="<?php p($l->t('Add a member')); ?>"/>
			<div id="members_search_result">

			</div>


		</div>

		<div id="memberslist">
			<table id="memberslist_table">
				<tr class="header">
					<td class="username"><?php p($l->t('Username')); ?></td>
					<td class="level"><?php p($l->t('Level')); ?></td>
					<td class="status"><?php p($l->t('Status')); ?></td>
					<td class="joined"><?php p($l->t('Joined')); ?></td>
				</tr>
			</table>

			<script id="tmpl_member" type="text/template">
				<tr class="entry" member-id="%username%" member-level="%level%"
					member-levelstring="%levelstring%" member-status="%status%">
					<td class="username">%username%</td>
					<td class="level">%levelstring%</td>
					<td class="status">%status%</td>
					<td class="joined">%joined%</td>
				</tr>
			</script>
		</div>

		<div id="rightpanel">
			<div class="lightenbg"></div>
			<div id="memberdetails">
				<div id="member_name"></div>
				<div id="member_levelstatus"></div>
				<input id="remmember" type="submit" value="<?php p($l->t('Kick this member')); ?>"/>
				<div id="member_request">
					<input id="joincircle_acceptrequest" type="submit"
						   value="<?php p($l->t('Accept the request')); ?>"/>
					<input id="joincircle_rejectrequest" type="submit"
						   value="<?php p($l->t('Dismiss the request')); ?>"/>
				</div>
			</div>

		</div>
	</div>


</div>
