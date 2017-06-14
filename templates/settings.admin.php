<?php


script('circles', 'admin');
style('circles', 'admin');

?>

<div class="section" id="circles">
	<h2><?php p($l->t('Circles')) ?></h2>

	<table cellpadding="10">
		<tr>
			<td colspan="2" class="left">Allow Federated Circles:<br/>
				<em>Circles from different Nextcloud can be linked together.</em></td>
			<td>
				<input type="checkbox" value="1" id="allow_federated_circle"/>
			</td>

		</tr>
	</table>
</div>