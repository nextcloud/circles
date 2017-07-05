<?php


script('circles', 'admin');
style('circles', 'admin');

?>

<div class="section" id="circles">
	<h2><?php p($l->t('Circles')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<!--<tr class="lane">
			<td colspan="2" class="left">Allow Group Linking:<br/>
				<em>Groups can be linked to Circles.</em></td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_linked_groups"/>
			</td>
		</tr>-->
		<tr class="lane">
			<td colspan="2" class="left">Allow Federated Circles:<br/>
				<em>Circles from different Nextcloud can be linked together.</em></td>
			<td class="right">
				<input type="checkbox" value="1" id="allow_federated_circles"/>
			</td>
		</tr>
	</table>
</div>