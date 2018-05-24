<p>Срок действия авторизации:&nbsp;<?=date('d.m.Y H:i:s', $this->expire);?></p>
<table class="table table-hover">
	<tr class="info">
		<th>
			Дата
		</th>
		<th>
			ФИО исполнителя
		</th>
		<th>
			Время
		</th>
	</tr>
	<? if (count($api_result) == 0) { ?>
		<tr>
			<td colspan="3">Ничего не найдено.</td>
		</tr>
	<? } else { ?>
		<? foreach ($api_result as $user) { ?>
			<? foreach ($user['dates'] as $key => $item) { ?>
				<tr>
					<td><?=$key;?></td>
					<td><?=$user['user_fio'];?></td>
					<td><?=date("H:i:s", mktime(0, 0, $item));?></td>
				</tr>
			<? } ?>
		<? } ?>
		<!--<tr><td colspan="3"><?=print_r($api_result);?></td></tr>-->
	<? } ?>
</table>