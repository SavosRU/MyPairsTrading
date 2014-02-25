<h2>Открываем новый парный трейд:</h2>
<form method="GET">
<input type="hidden" name="action" value="add_trade">
<table class="table table-condensed">
	<thead>
		<tr></tr>
		<tr>
			<th>1-ый Тикер:</th>
			<th>Цена:</th>
			<th>Количество:</th>
			<th>2-ой Тикер:</th>
			<th>Цена:</th>
			<th>Количество:</th>
			<th>Вид Сделки:</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><input type="text" class="form-control" name="t1" size="8" placeholder="Тикер"></td>
			<td><input type="text" class="form-control" name="p1" size="8" placeholder="Цена"></td>
			<td><input type="text" class="form-control" name="n1" size="8" placeholder="1"></td>
			<td><input type="text" class="form-control" name="t2" size="8" placeholder="Тикер"></td>
			<td><input type="text" class="form-control" name="p2" size="8" placeholder="Цена"></td>
			<td><input type="text" class="form-control" name="n2" size="8" placeholder="1"></td>
			<td><select name="type" class="form-control"><option>LONG</option><option>SHORT</option></select></td>
		</tr>
	</tbody>
</table>
<input class="btn btn-primary" type="submit" value="Добавить сделку">
</form>