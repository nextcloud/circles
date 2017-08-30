<?php


use OCA\Circles\AppInfo\Application;

script(Application::APP_NAME, 'admin');
style(Application::APP_NAME, 'admin');

?>

<div class="section" id="circles">
	<h2><?php p($l->t('Circles')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Allow Group Linking:')); ?><br/>
				<em><?php p($l->t('Groups can be linked to Circles.')); ?></em></td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_linked_groups"/>
			</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Allow Federated Circles:')); ?><br/>
				<em><?php p($l->t('Circles from different Nextclouds can be linked together.')); ?></em>
			</td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_federated_circles"/>
			</td>
		</tr>
	</table>
</div>
