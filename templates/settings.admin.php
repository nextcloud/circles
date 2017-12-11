<?php


use OCA\Circles\AppInfo\Application;

script(Application::APP_NAME, 'admin');
style(Application::APP_NAME, 'admin');

?>

<div class="section" id="circles">
	<h2><?php p($l->t('Circles')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Async Testing:')); ?><br/>
				<em id="test_async_result"></em></td>
			<td class="right">
				<input type="button" value="<?php p($l->t('initiate async test in Circles')); ?>" id="test_async_start"/>
				<input type="button" value="<?php p($l->t('reset test result')); ?>" id="test_async_reset"/>
				<input type="button" value="<?php p($l->t('Test underway. Please wait.')); ?>" id="test_async_wait"/>
			</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Allow linking of groups:')); ?><br/>
				<em><?php p($l->t('Groups can be linked to circles.')); ?></em></td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_linked_groups"/>
			</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Allow federated circles:')); ?><br/>
				<em><?php p($l->t('Circles from different Nextclouds can be linked together.')); ?></em>
			</td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_federated_circles"/>
			</td>
		</tr>
		<tr class="lane">
			<td colspan="2" class="left"><?php p($l->t('Enable audit:')); ?><br/>
				<em><?php p($l->t('Actions of circles, members and sharing can be audited.')); ?></em>
			</td>
			<td class="right">
				<input type="checkbox" value="1" id="enable_audit"/>
			</td>
		</tr>
	</table>
</div>
