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

use OCA\Circles\Api\v1\Circles;
use OCA\Circles\AppInfo\Application;

script(Application::APP_NAME, 'vendor/notyf');
style(Application::APP_NAME, 'notyf');

Circles::addJavascriptAPI();
script(
	Application::APP_NAME, [
				 'circles.app.elements', 'circles.app.actions',
				 'circles.app.navigation', 'circles.app.settings',
				 'circles.app', 'circles.app.results.circles', 'circles.app.results.members',
				 'circles.app.results.groups', 'circles.app.results.links'
			 ]
);

style(Application::APP_NAME, 'navigation');
?>


<div id="app-navigation" class="noborder" style="position: relative">
	<div class="navigation-element" style="height: 100%; padding-top: 15px">
		<input id="circles_new_name" type="text"
			   placeholder="<?php p($l->t('Create a new circle')); ?>"/>
		<select id="circles_new_type" style="display: none;" class="select_none">
			<option value="" style="font-style: italic">&nbsp;&nbsp;&nbsp;&nbsp;<?php p(
					$l->t("Select a circle type")
				); ?></option>
			<?php

			// Personal Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PERSONAL]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_PERSONAL,
						$l->t("Create a personal circle")
					)
				);
			}

			// Public Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PUBLIC]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_PUBLIC,
						$l->t("Create a public circle")
					)
				);
			}

			// Closed Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_CLOSED]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_CLOSED,
						$l->t("Create a closed circle")
					)
				);
			}

			// Secret Circle
			if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_SECRET]) {
				print_unescaped(
					sprintf(
						'<option value="%s">%s</option>',
						\OCA\Circles\Model\Circle::CIRCLES_SECRET,
						$l->t("Create a secret circle")
					)
				);
			}

			?>

		</select>
		<input id="circles_new_submit" type="submit" value="<?php p($l->t('Creation')); ?>" style="display: none;"/>

		<div id="circles_new_type_definition" style="display: none;">
			<div id="circles_new_type_1"><b>
					<?php p(
						$l->t(
							"A personal circle is a list of users known only to the owner."
						)
					); ?>
				</b><br/>
				<?php p(
					$l->t(
						"This is the right option if you want to do recurrent sharing with the same list of local users."
					)
				); ?>
			</div>
			<div id="circles_new_type_2"><b>
					<?php p(
						$l->t(
							"A secret circle is an hidden group that can only be seen by its members or by people knowing the exact name of the circle."
						)
					); ?></b><br/><?php p(
					$l->t(
						"Non-members won't be able to find your secret circle using the search bar."
					)
				); ?>
			</div>
			<div id="circles_new_type_4"><b><?php p(
						$l->t(
							"Joining a closed circle requires an invitation or confirmation by a moderator."
						)
					); ?>
				</b><br/><?php p(
					$l->t(
						"Anyone can find and request an invitation to the circle; but only members will see who\'s in it and get access to it\'s shared items."
					)
				); ?>
			</div>
			<div id="circles_new_type_8"><b><?php p(
						$l->t(
							"A public circle is an open group visible to anyone willing to join."
						)
					); ?></b><br/><?php p(
					$l->t(
						"Anyone can see, join, and access the items shared within the circle."
					)
				); ?>
			</div>
		</div>
	</div>
	<div id="circles_list">

		<?php
		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PERSONAL]) {
			print_unescaped(
				'<div circle-type="' . \OCA\Circles\Model\Circle::CIRCLES_PERSONAL . '">' . $l->t(
					'Personal circles'
				) . '</div>'
			);
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_PUBLIC]) {
			print_unescaped(
				'<div circle-type="' . \OCA\Circles\Model\Circle::CIRCLES_PUBLIC . '">' . $l->t(
					'Public circles'
				) . '</div>'
			);
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_CLOSED]) {
			print_unescaped(
				'<div circle-type="' . \OCA\Circles\Model\Circle::CIRCLES_CLOSED . '">' . $l->t(
					'Closed circles'
				) . '</div>'
			);
		}

		if ($_['allowed_circles'][\OCA\Circles\Model\Circle::CIRCLES_SECRET]) {
			print_unescaped(
				'<div circle-type="' . \OCA\Circles\Model\Circle::CIRCLES_SECRET . '">' . $l->t(
					'Secret circles'
				) . '</div>'
			);
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
			<div class="lightenbg">
				<input id="adminsettingscircle" type="submit"
					   value="<?php p($l->t('Settings')); ?>"/>
			</div>
			<div id="name"></div>
			<div id="type"></div>


			<div id="circle-actions">
				<div id="circle-actions-buttons">
					<div id="joincircle_invit"><?php p(
							$l->t("Pending invitation to join this circle")
						); ?></div>
					<div id="joincircle_request"><?php p(
							$l->t('You have a pending request to join this circle')
						); ?></div>
					<button id="circle-actions-add" class="icon-add-user"
							title="<?php p($l->t('Add a member')); ?>"></button>
					<button id="circle-actions-group" class="icon-link-group"
							title="<?php p($l->t('Link a group')); ?>"></button>
					<button id="circle-actions-link" class="icon-public"
							title="<?php p($l->t('Link a circle')); ?>"></button>
					<button id="circle-actions-join" class="icon-join"
							title="<?php p($l->t('Join this circle')); ?>"></button>
					<button id="circle-actions-delete" class="icon-delete"
							title="<?php p($l->t('Delete circle')); ?>"></button>
					<button id="circle-actions-settings" class="icon-settings-dark"
							title="<?php p($l->t('Edit circle')); ?>"></button>
				</div>

				<div id="circle-actions-more">
					<input id="joincircle_acceptinvit" type="submit"
						   value="<?php p($l->t('Accept the invitation')); ?>"/>
					<input id="joincircle_rejectinvit" type="submit"
						   value="<?php p($l->t('Decline the invitation')); ?>"/>
					<input id="joincircle" type="submit"
						   value="<?php p($l->t('Join this circle')); ?>"/>
					<input id="leavecircle" type="submit"
						   value="<?php p($l->t('Leave this circle')); ?>"/>
					<input id="addmember" type="text"
						   placeholder="<?php p($l->t('Add a member')); ?>"/>
					<input id="linkgroup" type="text"
						   placeholder="<?php p($l->t('Link a group')); ?>"/>
					<input id="linkcircle" type="text"
						   placeholder="<?php p($l->t('Link to a circle')); ?>"/>
					<button id="circle-actions-return" class="icon-close"
							title="<?php p($l->t('Return to menu')); ?>"></button>
				</div>


				<div id="members_search_result"></div>
				<div id="groups_search_result"></div>
			</div>
			<div id="circledata">
				<div id="circle_desc"></div>
				<div id="memberslist">
					<table id="memberslist_table">
						<tr class="header">
							<td class="username"><?php p($l->t('Username')); ?></td>
							<td class="level"><?php p($l->t('Level')); ?></td>
							<td class="status"><?php p($l->t('Status')); ?></td>
							<td class="joined"><?php p($l->t('Joined')); ?></td>
						</tr>
					</table>
					<br/><br/><br/><br/>

					<table id="groupslist_table">
						<tr class="header">
							<td class="groupid"><?php p($l->t('Group Name')); ?></td>
							<td class="level"><?php p($l->t('Level')); ?></td>
							<td class="joined"><?php p($l->t('Joined')); ?></td>
						</tr>
					</table>
					<br/><br/><br/><br/>

					<table id="linkslist_table">
						<tr class="header">
							<td class="address"><?php p($l->t('Link')); ?></td>
							<td class="status"><?php p($l->t('Status')); ?></td>
							<td class="linked"><?php p($l->t('Linked')); ?></td>
						</tr>
					</table>

					<script id="tmpl_member" type="text/template">
						<tr class="entry" member-id="%username%" member-type="%type%" member-level="%level%"
							member-levelString="%levelString%"
							member-status="%status%">
							<td class="username" style="padding-left: 15px;">%displayname%</td>
							<td class="level">
								<select class="level-select">
									<option value="1"><?php p($l->t('Member')); ?></option>
									<option value="4"><?php p($l->t('Moderator')); ?></option>
									<option value="8"><?php p($l->t('Admin')); ?></option>
									<option value="9"><?php p($l->t('Owner')); ?></option>
								</select>
							</td>
							<td class="status">
								<select class="status-select">
								</select>
							</td>
							<td class="joined">%joined%</td>
							<td>
								<div class="icon-checkmark" style="display: none;"></div>
							</td>
						</tr>
					</script>

					<script id="tmpl_group" type="text/template">
						<tr class="entry" group-id="%groupid%" group-level="%level%">
							<td class="groupid" style="padding-left: 15px;">%groupid%</td>
							<td class="level">
								<select class="level-select">
									<option value="1"><?php p($l->t('Member')); ?></option>
									<option value="4"><?php p($l->t('Moderator')); ?></option>
									<option value="8"><?php p($l->t('Admin')); ?></option>
								</select>
							</td>
							<td class="joined">%joined%</td>
						</tr>
					</script>

					<script id="tmpl_link" type="text/template">
						<tr class="entry" link-id="%id%" link-address="%address%"
							link-status="%status%">
							<td class="address" style="padding-left: 15px;">%token%@%address%</td>
							<td class="status">
								<select class="link-status-select">
								</select>
							</td>
							<td class="joined">%joined%</td>
						</tr>
					</script>

				</div>
			</div>
			<div id="settings-panel">
				<table id="settings-table">

					<tr>
						<td class="left">Name of the Circle</td>
						<td><input type="text" id="settings-name"/></td>
					</tr>

					<tr>
						<td class="left" style="vertical-align: top">Description</td>
						<td><textarea type="text" id="settings-desc"></textarea></td>
					</tr>


					<tr id="settings-entry-limit">
						<td class="left"><?php p($l->t('Members limit')); ?><br/>
							<span class="hint"><?php p(
									$l->t(
										'Change the limit to the number of members. (0: default, -1: unlimited)'
									)
								); ?></span>
						</td>
						<td><input type="text" value="" id="settings-limit"></td>
					</tr>

					<tr id="settings-entry-link">
						<td class="left"><?php p($l->t('Allow Federated Links')); ?><br/>
							<span class="hint"><?php p(
									$l->t(
										'Makes the circle federated, and enables sharing between federated circles'
									)
								); ?></span>
						</td>
						<td><input type="checkbox" value="1" id="settings-link"></td>
					</tr>
					<!--<tr id="settings-entry-link-files">
						<td class="left">Share Files With Linked Circles<br/>
							<span class="hint">Files that are locally shared with this circle will be shared with all Linked circles</span>
						</td>
						<td><input type="checkbox" value="1" id="settings-link-files"></td>
					</tr>-->
					<tr id="settings-entry-link-auto">
						<td class="left"><?php p($l->t('Accept Link Request Automatically')); ?>
							<br/>
							<span class="hint"><?php p(
									$l->t(
										'Warning: Enabling this will automatically accept new link requests from other circles.'
									)
								); ?></span>
						</td>
						<td><input type="checkbox" value="1" id="settings-link-auto"></td>
					</tr>

					<tr>
						<td colspan="2" style="text-align: center;">
							<input type="submit" id="settings-submit"
								   value="<?php p($l->t('Save settings')); ?>"/>
						</td>
					</tr>
				</table>


				<div>
				</div>

			</div>
		</div>
