<?php


use OCA\Circles\AppInfo\Application;

script(Application::APP_NAME, 'admin');
style(Application::APP_NAME, 'admin');

?>

<div class="section" id="circles">
	<h2><?php p($l->t('Circles')) ?></h2>

	<p>
		<label><?php p($l->t('Maximum number of members per circle')); ?></label><br />
		<input type="text" id="members_limit" />
	</p>
	<p>
		<input type="checkbox" value="1" id="allow_linked_groups" class="checkbox" />
		<label for="allow_linked_groups"><?php p($l->t('Allow linking of groups')); ?></label>
		<em><?php p($l->t('Groups can be linked to circles.')); ?></em>
	</p>
	<p>
		<input type="checkbox" value="1" id="allow_federated_circles" class="checkbox"/>
		<label for="allow_federated_circles"><?php p($l->t('Allow federated circles')); ?></label>
		<em><?php p($l->t('Circles from different Nextclouds can be linked together.')); ?></em>
	</p>
	<p>
		<input type="checkbox" value="1" id="skip_invitation_to_closed_circles" class="checkbox"/>
		<label for="skip_invitation_to_closed_circles"><?php p($l->t('Skip invitation step to closed circles')); ?></label>
		<em><?php p($l->t('Moderators can add member to closed circles without user approval.')); ?></em>
	</p>
</div>
