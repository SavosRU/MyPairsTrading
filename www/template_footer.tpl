</div> 	<!-- end of div class="container" -->

<!-- Start of Footer-Div -->
<nav class="navbar navbar-default navbar-fixed-bottom navbar-inverse" role="navigation"><div class="container">
	Align nav links, forms, buttons, or text, using the .navbar-left or .navbar-right utility classes. <br>
	Both classes will add a CSS float in the specified direction. 
</div></nav>
<!-- end of Footer-Div -->

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
<script>
	$(document).ready(function(){
		//$(function(){
		//	$("#tradestable").tablesorter();
		//});
		$('.btn').tooltip('hide')					
	});	
	
	jQuery(function($) {
		$('form[data-form-confirm]').submit(function(e) {
			// Тут я добавил свою собственную часть, чтобы вставлять в форму перед отправкой недостающие мне скрытые поля
			// в зависимости от того. какая имено кнопка была на странице нажата
			var btn = $( ":input[type=submit]:focus" );
			var btn_name = btn.attr('name');
			var btn_value = btn.attr('value');
			var btn_title = btn.attr('modaltitle');
			var newfield = '<input type="hidden" name="'+btn_name+'" value="'+btn_value+'" />';
			var new_confirm_btn = 'Да, ' + btn_title + '!';
			if (btn_name =="edit_btn") {
				var btn_data = btn.attr('modalvalue');
				//alert(btn_data);
				var form_data = btn.attr('modalvalue').split("||"); 
				// получаем массив вот таких данных: trade_id, ticker1, ticker2, start_price1, start_price2, end_price1, end_price2, pos1, pos2, type-long-or-short
				//alert(form_data);
			}
			//alert(btn.attr('modaltitle'));
			// Конец моей собственной вставки
			
			e.preventDefault();
	 
			// а тут я пробую тоже изменить стандартное поведение и перехватить вызов модального окна диалога
			// таким образом, чтобы нужное окно выбиралось в зависимости от настроек НАЖАТОЙ КНОПКИ, а не всей ФОРМЫ
			//var form = $(this),
			//modal = $('#' + form.attr('data-form-confirm'))

	 		var form = $(this),
			modal = $('#' + btn.attr('data-form-confirm'))

	 
	 		// еще одно добавление: меняем надпись на кнопке диалога подтверждения в зависимости от текста на нажатойв форме кнопке
	 		// и вставляем скрытое поле с нужными нам данными
			//alert(form.attr('id'));
			//alert(modal.find('.btn-confirm').html());
			form.append(newfield);
	 		modal.find('.btn-confirm').html(new_confirm_btn);
			
			// И еще одно: если у нас была нажата кнопка Edit_Btn то будет вызван диалог редактирования сделки...
			// Так вот в нем надо правильно пред-заполнить поля с текущими данными!!!
			if (btn_name =="edit_btn") {
				//alert(modal.find("#t1").attr('value'));
				//alert(modal.find("#type").val());
				modal.find("#trade_id").attr('value',form_data[0]);	// Trade-ID
				modal.find("#t1").attr('value',form_data[1]);		// Ticker-1
				modal.find("#t2").attr('value',form_data[2]);		// Ticker-2
				modal.find("#n1").attr('value',form_data[7]);		// PosSize-1
				modal.find("#n2").attr('value',form_data[8]);		// PosSize-2
				modal.find("#sp1").attr('value',form_data[3]);		// StartPrice-1
				modal.find("#sp2").attr('value',form_data[4]);		// StartPrice-2
				modal.find("#ep1").attr('value',form_data[5]);		// EndPrice-1
				modal.find("#ep2").attr('value',form_data[6]);		// EndPrice-2
				modal.find("#type").val(form_data[9]);				// Long or Short???
				modal.find("#date1").val(form_data[10]);			// Start Date
				modal.find("#date2").val(form_data[11]);			// Stop Date
				if(form_data[12] == 1) {
					modal.find("#date1").attr('readonly', true);
					modal.find("#date2").attr('readonly', true);
				}
			}
			
			// Дальше идет стандартный код из документации!!!
			modal.modal({
				show: true,
				backdrop: true
			});
	 
	 		
			modal.find('.btn-confirm').click(function() {
				modal.modal('hide');
				form.unbind('submit').submit();
			});
			
		});
	});


</script>
</body>
</html>