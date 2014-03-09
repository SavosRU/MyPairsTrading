<?php
//phpinfo();
error_reporting(E_ALL);

include_once("MyDB.class.php");
include_once("functions_lib.php");

define ("FORM_METHOD",	"GET");			// метод отправки форм для отладки
//define ("FORM_METHOD",	"POST");	// метод отправки форм при обычной работе

define ("SQL_HOST",		"localhost");
define ("SQL_LOGIN",	"root");
define ("SQL_PWD",		"");
define ("SQL_BASE",		"pairs_trading");

define ("EOL",			"<br />\n");

define ("FIXTP",		"1.5");			// процент прибыли от BP при котором мы фиксируем эту самую прбыль (FIX TakeProfit -> FIXTP)
define ("FIXSL",		"-2");			// процент потерь от BP при котором мы фиксируем эту самую потерю  (FIX StopLoss   -> FIXSL)

define ("DEPO",			"10000");		// стартовый размер депозита в долларах США
define ("TOTALBP",		"50000");		// разрешенный размер BuyPower при текущем плече "1-к-5"
define ("PAIRBP",		"2000");		// то есть при равной "длине ног" у пары мы получаем на каждую акцию по половине этой суммы (сейчас это $1000)
define ("OVERNIGHT",	"0.00022");		// "овернайт" - выплаты за использование денежных средств (BP) в течение одной ночи за каждую ТЫСЯЧУ ДОЛЛАРОВ
define ("FEES",			"0.00055");		// "физы" - комиссия за операцию с каждой ТЫСЯЧЕЙ ДОЛЛАРОВ, причем на одну пару берем ДВЕ такие комиссии 
										// (открытие сделки - ОДНА операция, закрытие сделки - уже ВТОРАЯ!!!)

//*****************************************************************************************************************************************************************//
// Попытка противостоять постоянному кешированию страницы - во время разработки это крайне неудобно!!!
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");
  
// также на всякий случай передаем браузеру хедер, сообщающий используемую нами кодировку
header('Content-Type: text/html; charset=utf-8');
// и еще попробуем задать авторефреш каждые 5 секунд
//header('refresh:5; url=index.php'); // нет, не стоит... плагин для Хрома работает лучше и всегда легко его включить и отключить
//*****************************************************************************************************************************************************************//

										
$db = new MyDB(SQL_HOST, SQL_LOGIN, SQL_PWD, SQL_BASE);

//Теперь проверяем был ли при вызове скрипта использован какой-либо POST-запрос (ну или GET-запрос с параметрами)
if (isset($_GET['action'])) {
    // Если запрос происходит через GET-метод, то скорее всего это тестинг... Значит можно печатать диагностические сообщения!
    //print "<hr><b><font color=\"red\">DEBUG MODE!</font></b><hr>";
    define("DEBUG", 0);
    define("isGET", 1);
    define("isPOST", 0);
	define("isNORMAL", 0);
} elseif (isset($_POST['action'])) {
    // Если запрос происходит через POST-метод, то скорее всего это работа... Значит можно НЕ печатать диагностические сообщения!
    // Ведь лишняя диагностика нам только помешает
    print "<hr><b><font color=\"red\">SILENT MODE!</font></b><hr>";
    define("DEBUG", 0);
    define("isGET", 0);
    define("isPOST", 1);
	define("isNORMAL", 0);
} else {
    // в этом случае - явно просто заход на index.php и нам вообще особо выводить нечего
    define("DEBUG", 0);
    define("isGET", 0);
    define("isPOST", 0);
	define("isNORMAL", 1);
}

$form_newtrade = "";
$form_edittrade = "";
$form_confirm = "";

$trade_opened = "";
$trade_closed = "";
$trade_drafts = "";
$trade_removed = "";
$trade_updated = "";
$multibtn_form_result = '';

if(isGET OR isPOST) {
	$action = $_REQUEST['action'];
	// если у нас имеется GET-запрос или POST-запрос - проверяем значения полей
	// action у нас по умолчанию может принимать только следующие значения:
	// -> "add_trade" - открыть новый парный трейд
	// -> "del_trade" - завершить имеющийся парный трейд
	// -> "mod_trade" - модифицировать имеющийся парный трейд
	switch ($action) {
		case "add_trade":
			//if(DEBUG) print "Open New Trade!<br>\n".EOL;
			//print_r($_REQUEST);
			
			$t1 = strtoupper(trim($_REQUEST['t1']));
			//if(DEBUG) print "Первый тикер: <b>".$t1."</b>".EOL;
			$t2 = strtoupper(trim($_REQUEST['t2']));
    		//if(DEBUG) print "Второй тикер: <b>".$t2."</b>".EOL;
			
    		$p1 = strval(str_replace(',','.',trim($_REQUEST['p1'])));			
    		//if(DEBUG) print "Цена первой акции: <b>".$p1."</b>".EOL;
    		$p2 = strval(str_replace(',','.',trim($_REQUEST['p2'])));
    		//if(DEBUG) print "Цена второй акции: <b>".$p2."</b>".EOL;
			
    		$n1 = intval(trim($_REQUEST['n1']));
			//if(DEBUG) print "Первое количество: <b>".$n1."</b>".EOL;    		
    		$n2 = intval(trim($_REQUEST['n2']));
			//if(DEBUG) print "Второе количество: <b>".$n2."</b>".EOL;
			
			$type = trim($_REQUEST['type']);
			
			$pair_data['t1'] = $t1;
			$pair_data['t2'] = $t2;
			$pair_data['p1'] = $p1;
			$pair_data['p2'] = $p2;
			$pair_data['n1'] = $n1;
			$pair_data['n2'] = $n2;
			$pair_data['type'] = $type;
			
			$totalPrice = $p1*$n1 - $p2*$n2;
			$myPair = $t1."*".$n1."-".$t2."*".$n2;
						
			if (addNewPairsTrade("opened",$db,$pair_data) == 1) {
				$trade_opened .= "<div class=\"alert alert-success alert-dismissable\">";
				$trade_opened .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_opened .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Открыли <b>".$type."</b> сделку на паре <b>".$myPair."</b> по цене <b>$".$totalPrice."</b>";
				$trade_opened .= "</div>";
			} else {
				$trade_opened .= "<div class=\"alert alert-danger alert-dismissable\">";
				$trade_opened .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_opened .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ ОТКРЫТИИ НОВОЙ СДЕЛКИ!!!";
				$trade_opened .= "</div>";
			}
			
			break;
		case "cls_trade":
			//if(DEBUG) print "Delete Exists Trade!<br>\n";
			//print_r($_REQUEST);
			$tid = strval(str_replace(',','.',trim($_REQUEST['trade_id'])));	// Trade ID => "tid" - айдишник сделки			
    		//if(DEBUG) print "Цена первой акции: <b>".$p1."</b>".EOL;
    		$ep1 = strval(str_replace(',','.',trim($_REQUEST['end_p1'])));	// цена акции №1 на момент закрытия парной сделки
    		//if(DEBUG) print "Цена первой акции: <b>".$p1."</b>".EOL;
    		$ep2 = strval(str_replace(',','.',trim($_REQUEST['end_p2'])));
    		//if(DEBUG) print "Цена второй акции: <b>".$p2."</b>".EOL;	// цена акции №2 на момент закрытия парной сделки
			
			if (closePairsTrade($db,$tid,$ep1,$ep2) == 1) {
				$trade_opened .= "<div class=\"alert alert-success alert-dismissable\">";
				$trade_opened .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_opened .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Закрываем сделку с айдишником <b>".$tid."</b> по ценам входящих в пару акций <b>$".$ep1."</b> и <b>$".$ep2."</b> соответственно...";
				$trade_opened .= "</div>";
			} else {
				$trade_opened .= "<div class=\"alert alert-danger alert-dismissable\">";
				$trade_opened .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_opened .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ ЗАКРЫТИИ СДЕЛКИ <b>".$tid."</b>!!!";
				$trade_opened .= "</div>";
			}
						
			break;
		case "multi_btn_form":
			// когда мы перехватываем вот такое событие, как запрос из многокнопочной формы, нам надо теперь понять какая же именно кнопка была нажата???
			if(DEBUG) print "multi_btn_form detected!";
			$tid = $_REQUEST['id'];	// Trade ID => "tid" - айдишник сделки	
			if(isset($_REQUEST['del_btn'])) {
				// удаляем запись с айдишником, переданным в этом поле
				//$tid = $_REQUEST['del_btn'];	// Trade ID => "tid" - айдишник сделки	
				if (delPairsTrade($db,$tid) == 1) {
					$multibtn_form_result .= "<div class=\"alert alert-success alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Окончательно и безповоротно УДАЛИЛИ сделку с айдишником <b>".$tid."</b>";
					$multibtn_form_result .= "</div>";
				} else {
					$multibtn_form_result .= "<div class=\"alert alert-danger alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ УДАЛЕНИИ СДЕЛКИ <b>".$tid."</b>!!!";
					$multibtn_form_result .= "</div>";
				}
				//print "Удаляем запись о трейде с номером <b>".$tid."</b>".EOL;
			} elseif(isset($_REQUEST['draft_btn'])) {
				// переносим в "Черновики" запись с айдишником, переданным в этом поле
				//$tid = $_REQUEST['draft_btn'];	// Trade ID => "tid" - айдишник сделки	
				if (draftPairsTrade($db,$tid) == 1) {
					$multibtn_form_result .= "<div class=\"alert alert-success alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Перенесли в \"Черновики\" сделку с айдишником <b>".$tid."</b>";
					$multibtn_form_result .= "</div>";
				} else {
					$multibtn_form_result .= "<div class=\"alert alert-danger alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ ПЕРЕНОСЕ В \"ЧЕРНОВИКИ\" СДЕЛКИ <b>".$tid."</b>!!!";
					$multibtn_form_result .= "</div>";
				}
				//print "Переносим в \"Черновики\" запись о трейде с номером <b>".$trade_id."</b>".EOL;
			} elseif(isset($_REQUEST['run_btn'])) {
				// переносим в "Открытые" запись с айдишником, переданным в этом поле
				//$tid = $_REQUEST['run_btn'];	// Trade ID => "tid" - айдишник сделки	
				if (runPairsTrade($db,$tid) == 1) {
					$multibtn_form_result .= "<div class=\"alert alert-success alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Перенесли в \"Открытые\" сделку с айдишником <b>".$tid."</b>";
					$multibtn_form_result .= "</div>";
				} else {
					$multibtn_form_result .= "<div class=\"alert alert-danger alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ ПЕРЕНОСЕ В \"ОТКРЫТЫЕ\" СДЕЛКИ <b>".$tid."</b>!!!";
					$multibtn_form_result .= "</div>";
				}
				//print "Переносим в \"Открытые\" запись о трейде с номером <b>".$trade_id."</b>".EOL;
			} elseif(isset($_REQUEST['edit_btn'])) {
				// Открываем форму редактирования записи с айдишником, переданным в этом поле
				//$tid = $_REQUEST['edit_btn'];	// Trade ID => "tid" - айдишник сделки	
				if (editPairsTrade($db,$tid) == 1) {
					$multibtn_form_result .= "<div class=\"alert alert-success alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Изменили данные о сделке с айдишником <b>".$tid."</b>";
					$multibtn_form_result .= "</div>";
				} else {
					$multibtn_form_result .= "<div class=\"alert alert-danger alert-dismissable\">";
					$multibtn_form_result .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
					$multibtn_form_result .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ РЕДАКТИРОВАНИИ СДЕЛКИ <b>".$tid."</b>!!!";
					$multibtn_form_result .= "</div>";
				}
				//print "Будем редактировать запись о трейде с номером <b>".$trade_id."</b>".EOL;
			}
			break;
		case "edit_trade":
			if(DEBUG) print "Modify (UPDATE) Selected CLOSED Trade!";
			//print_r($_REQUEST);
			//exit;
			$t1 = strtoupper(trim($_REQUEST['t1']));
			$t2 = strtoupper(trim($_REQUEST['t2']));

    		$sp1 = strval(str_replace(',','.',trim($_REQUEST['sp1'])));
    		$sp2 = strval(str_replace(',','.',trim($_REQUEST['sp2'])));
    		$ep1 = strval(str_replace(',','.',trim($_REQUEST['ep1'])));
    		$ep2 = strval(str_replace(',','.',trim($_REQUEST['ep2'])));

    		$n1 = intval(trim($_REQUEST['n1']));
    		$n2 = intval(trim($_REQUEST['n2']));

			$type = trim($_REQUEST['type']);
			$tid = $_REQUEST['trade_id'];
			$date1 = $_REQUEST['date1'];
			$date2 = $_REQUEST['date2'];
			
			$pair_data['id'] = $tid; 	
			//$pair_data['t1'] = $t1;
			//$pair_data['t2'] = $t2;
			$pair_data['sp1'] = $sp1;
			$pair_data['sp2'] = $sp2;
			$pair_data['ep1'] = $ep1;
			$pair_data['ep2'] = $ep2;
			$pair_data['n1'] = $n1;
			$pair_data['n2'] = $n2;
			$pair_data['type'] = $type;
			$pair_data['date1'] = $date1;
			$pair_data['date2'] = $date2;
						
			//print_r($pair_data);
			//exit;
					
			if (editPairsTrade($db,$pair_data) == 1) {
				$trade_updated .= "<div class=\"alert alert-success alert-dismissable\">";
				$trade_updated .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_updated .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Изменили данные о сделке с айдишником <b>".$tid."</b>";
				$trade_updated .= "</div>";
			} else {
				$trade_updated .= "<div class=\"alert alert-danger alert-dismissable\">";
				$trade_updated .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
				$trade_updated .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> ОШИБКА ПРИ РЕДАКТИРОВАНИИ СДЕЛКИ <b>".$tid."</b>!!!";
				$trade_updated .= "</div>";
			}									
			break;
		case "del_trade":
			if(DEBUG) print "Delete Exists Trade!<br>\n";
			print_r($_REQUEST);
			$tid = strval(str_replace(',','.',trim($_REQUEST['id'])));	// Trade ID => "tid" - айдишник сделки			
    		//if(DEBUG) print "Цена первой акции: <b>".$p1."</b>".EOL;
    		$p1 = strval(str_replace(',','.',trim($_REQUEST['p1'])));	// цена акции №1 на момент закрытия парной сделки
    		//if(DEBUG) print "Цена первой акции: <b>".$p1."</b>".EOL;
    		$p2 = strval(str_replace(',','.',trim($_REQUEST['p2'])));
    		//if(DEBUG) print "Цена второй акции: <b>".$p2."</b>".EOL;	// цена акции №2 на момент закрытия парной сделки
			print "УДАЛЯЕМ сделку с айдишником <b>".$tid."</b> по ценам входящих в пару акций <b>$".$p1."</b> и <b>$".$p2."</b> соответственно...".EOL;
			break;
		case "run_trade":
			if(DEBUG) print "Activate (RUN!) Selected DRAFTS Trade!";
			break;			
		default:
			if(DEBUG) print "Что-то не так с запросом: не верный ACTION!!!";
	}
} 

//начинаем формировать страницу... сначала заголовок:
include_once("template_header.tpl");

// у нас при каждой операции добавления, удаления, модификации записей о парных сделках будут выводиться сообщения с кнопкой "ПРОДОЛЖИТЬ"
// и в этом случае нам не нужны отрисовки всех таблиц, особенно тех, где требуется запрос к гуглФинанс.
// Поэтому готовить эти таблицы будет только в случае "обычного" посещения главной страницы проекта (без GET и POST запросов от форм)
if(isNORMAL) {
	
	//выводим форму добавления нового парного трейда
	$form_header = "Открыть новую парную сделку:";
	ob_start();
	include("template_modal_newtrade.tpl");
	$form_newtrade = ob_get_contents();
	ob_end_clean();
	
	//выводим диалоговое окно с формой "Подтвердить/Отменить" то или иное действие
	$form_header = "Подтвердить или Отменить?";
	ob_start();
	include("template_modal_confirm.tpl");
	$form_confirm = ob_get_contents();
	ob_end_clean();

	//выводим форму редактирования выбранного парного трейда
	$form_header = "Редактировать парную сделку:";
	ob_start();
	include("template_modal_edittrade.tpl");
	$form_edittrade = ob_get_contents();
	ob_end_clean();
	
	
	//Проверяем, есть ли сейчас хоть одна запись в таблице текущих трейдов
	// И если есть - выводим таблицу, а если нет - выводим форму добавления нового трейда
	$table_opened = "";
	$table_opened .= "<div class=\"alert alert-danger alert-dismissable\">";
	$table_opened .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
	$table_opened .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Пока нет ни одной открытой парной сделки!";
	$table_opened .= "</div>";
	
	$td = getTradeStructureTableFromMYSQL("opened", $db) ;
	if($td != NULL) {
		// будем выводить таблицу текущих сделок с формой их закрытия (модернизации)
		$rowsnum = count($td); // сколько строк таблицы нам вернули для вывода на страницу???
		$table_tr = "";
		for($i=1; $i<$rowsnum+1; $i++) {
			// начинаем строку таблицы
			$trclass = $td[$i][11]['class'];
			$modalvalue = implode("||",$td[$i][12]); // для передачи данных в форму редактирования сделки!!!
			//print_r($modalvalue);
			//exit;
			$table_tr .= "<tr class=\"".$trclass."\">\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][1]['spanclass']."\"><b>".$td[$i][1]['content']."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][2]['spanclass']." glyphicon ".$td[$i][2]['content']."\"></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><b>".$td[$i][3]['content']."</b></td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][4]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][5]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][6]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][7]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".$td[$i][8]['content']."</td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][9]['spanclass']."\"><b>".number_format($td[$i][9]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][10]['spanclass']."\"><b>".number_format($td[$i][10]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][11]['spanclass']."\"><b>".number_format($td[$i][11]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><form method=\"".FORM_METHOD."\" data-form-confirm=\"myModalConfirm\"><input type=\"hidden\" name=\"action\" value=\"cls_trade\">";
			$table_tr .= "<input type=\"hidden\" name=\"trade_id\" value=\"".$td[$i][12]['tid']."\">";		// отправляем айдишник СДЕЛКИ, а не ПАРЫ!!! Потому что у нас в дальнейшем может снова и снова торговаться одна и та же пара
			$table_tr .= "<input type=\"hidden\" name=\"end_p1\" value=\"".$td[$i][6]['class']."\">";		// вот тут мы и используем ту самую "неправильность" от шестого столбца
			$table_tr .= "<input type=\"hidden\" name=\"end_p2\" value=\"".$td[$i][6]['spanclass']."\">";	// и вот тут тоже используем ту самую "неправильность" от шестого столбца
			
			$table_tr .= " <button type=\"submit\" name=\"edit_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalEditTrade\" class=\"btn btn-warning btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" ";		
			$table_tr .= " modalvalue = \"".$modalvalue."\" title=\"Редактировать Сделку\" modaltitle=\"Редактировать Сделку\"><span class=\"glyphicon glyphicon-edit\"></span></button>";
			
			$table_tr .= " <button type=\"submit\" data-form-confirm=\"myModalConfirm\" class=\"btn btn-danger btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Закрыть Сделку\" modaltitle=\"Закрыть Сделку\" name=\"closeBtn\" value=\"btnClose\"><span class=\"glyphicon glyphicon-download\"></span></button>";
			//$table_tr .= "<input class=\"btn btn-danger btn-xs\" type=\"submit\" value=\"Закрыть\">";
			$table_tr .= "</form></td>\n</tr>\n";
		}
		// готовим фрагмент вывода таблицы открытых на данный момент парных сделок	
		$table_header = "Сейчас торгуются эти пары:";
		$table_panel = "panel-primary";
		ob_start();
		include("template_table_trades.tpl");
		$table_opened = ob_get_contents();
		ob_end_clean();
	}
	
	
	// теперь повторяем все то же самое - но уже для ранее закрытых сделок!
	$table_closed = "";
	$table_closed .= "<div class=\"alert alert-danger alert-dismissable\">";
	$table_closed .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
	$table_closed .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Пока нет ни одной закрытой парной сделки!";
	$table_closed .= "</div>";
	
	$td = getTradeStructureTableFromMYSQL("closed", $db) ;
	if($td != NULL) {
		// будем выводить таблицу уже закрытых сделок с формой их модернизации или удаления
		$rowsnum = count($td); // сколько строк таблицы нам вернули для вывода на страницу???
		$table_tr = "";
		for($i=1; $i<$rowsnum+1; $i++) {
			// начинаем строку таблицы
			$trclass = $td[$i][11]['class'];
			$modalvalue = implode("||",$td[$i][12]); // для передачи данных в форму редактирования сделки!!!
			//print_r($modalvalue);
			//exit;
			$table_tr .= "<tr class=\"".$trclass."\">\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][1]['spanclass']."\"><b>".$td[$i][1]['content']."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][2]['spanclass']." glyphicon ".$td[$i][2]['content']."\"></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><b>".$td[$i][3]['content']."</b></td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][4]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][5]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][6]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][7]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".$td[$i][8]['content']."</td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][9]['spanclass']."\"><b>".number_format($td[$i][9]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][10]['spanclass']."\"><b>".number_format($td[$i][10]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][11]['spanclass']."\"><b>".number_format($td[$i][11]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\">";
			$table_tr .= " <form class=\"form-inline\" role=\"form\" id=\"multisubmit\" method=\"".FORM_METHOD."\" data-form-confirm=\"myModalConfirm\"><input type=\"hidden\" name=\"action\" value=\"multi_btn_form\">";
			$table_tr .= "<input type=\"hidden\" name=\"id\" value=\"".$td[$i][12]['tid']."\">";	// отправляем айдишник СДЕЛКИ, а не ПАРЫ!!! Потому что у нас в дальнейшем может снова и снова торговаться одна и та же пара
			//$table_tr .= "<button type=\"submit\" name=\"run_btn\" value=\"".$td[$i][12]['tid']."\" class=\"btn btn-success btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Перенести в Открытые\"><span class=\"glyphicon glyphicon-check\"></span></button>";
			$table_tr .= " <button type=\"submit\" name=\"edit_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalEditTrade\" class=\"btn btn-warning btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" ";		
			$table_tr .= " modalvalue = \"".$modalvalue."\" title=\"Редактировать Сделку\" modaltitle=\"Редактировать Сделку\"><span class=\"glyphicon glyphicon-edit\"></span></button>";
			
			$table_tr .= " <button type=\"submit\" name=\"draft_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalConfirm\" class=\"btn btn-info btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Перенести в Черновики\" modaltitle=\"Перенести в Черновики\"><span class=\"glyphicon glyphicon-share\"></span></button>";
			$table_tr .= " <button type=\"submit\" name=\"del_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalConfirm\" class=\"btn btn-danger btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Удалить Сделку\" modaltitle=\"Удалить Сделку\"><span class=\"glyphicon glyphicon-remove\"></span></button>";
			$table_tr .= "</form></td>\n</tr>\n";
		}
		// готовим фрагмент вывода таблицы уже закрытых на данный момент парных сделок	
		$table_header = "Уже завершены сделки по этим парам:";
		$table_panel = "panel-primary";
	 	$read_only = "";
		ob_start();
		include("template_table_trades.tpl");
		$table_closed = ob_get_contents();
		ob_end_clean();
	}
	
	// теперь повторяем все то же самое - но уже для "черновиков" (drafts) сделок!
	$table_drafts = "";
	$table_drafts .= "<div class=\"alert alert-danger alert-dismissable\">";
	$table_drafts .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>";
	$table_drafts .= "<span class=\"glyphicon glyphicon-exclamation-sign lead\"></span> Пока нет ни одной парной сделки в черновиках!";
	$table_drafts .= "</div>";
	
	$td = getTradeStructureTableFromMYSQL("drafts", $db) ;
	//print_r($td);
	//exit;
	if($td != NULL) {
		// будем выводить таблицу уже закрытых сделок с формой их модернизации или удаления
		$rowsnum = count($td); // сколько строк таблицы нам вернули для вывода на страницу???
		$table_tr = "";
		for($i=1; $i<$rowsnum+1; $i++) {
			// начинаем строку таблицы
			$trclass = $td[$i][11]['class'];
			$modalvalue = implode("||",$td[$i][12]); // для передачи данных в форму редактирования сделки!!!
			$table_tr .= "<tr class=\"".$trclass."\">\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][1]['spanclass']."\"><b>".$td[$i][1]['content']."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][2]['spanclass']." glyphicon ".$td[$i][2]['content']."\"></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><b>".$td[$i][3]['content']."</b></td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][4]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][5]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][6]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".number_format($td[$i][7]['content'], 2, ',', ' ' )."</td>\n";
			$table_tr .= "	<td class=\"text-center\">".$td[$i][8]['content']."</td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][9]['spanclass']."\"><b>".number_format($td[$i][9]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][10]['spanclass']."\"><b>".number_format($td[$i][10]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\"><span class=\"".$td[$i][11]['spanclass']."\"><b>".number_format($td[$i][11]['content'], 2, ',', ' ' )."</b></span></td>\n";
			$table_tr .= "	<td class=\"text-center\">";
			$table_tr .= " <form class=\"form-inline\" role=\"form\" method=\"".FORM_METHOD."\" data-form-confirm=\"myModalConfirm\"><input type=\"hidden\" name=\"action\" value=\"multi_btn_form\">";
			$table_tr .= "<input type=\"hidden\" name=\"id\" value=\"".$td[$i][12]['tid']."\">";	// отправляем айдишник СДЕЛКИ, а не ПАРЫ!!! Потому что у нас в дальнейшем может снова и снова торговаться одна и та же пара
			$table_tr .= "<button type=\"submit\" name=\"run_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalConfirm\" class=\"btn btn-success btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Открыть Сделку\" modaltitle=\"Открыть Сделку\"><span class=\"glyphicon glyphicon-check\"></span></button>";
			//$table_tr .= " <button type=\"submit\" name=\"edit_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalEditTrade\" class=\"btn btn-warning btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Редактировать Сделку\" modaltitle=\"Редактировать Сделку\"><span class=\"glyphicon glyphicon-edit\"></span></button>";
			$table_tr .= " <button type=\"submit\" name=\"edit_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalEditTrade\" class=\"btn btn-warning btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" ";		
			$table_tr .= " modalvalue = \"".$modalvalue."\" title=\"Редактировать Сделку\" modaltitle=\"Редактировать Сделку\"><span class=\"glyphicon glyphicon-edit\"></span></button>";
			
			$table_tr .= " <button type=\"submit\" name=\"del_btn\" value=\"".$td[$i][12]['tid']."\" data-form-confirm=\"myModalConfirm\" class=\"btn btn-danger btn-xs\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Удалить Сделку\" modaltitle=\"Удалить Сделку\"><span class=\"glyphicon glyphicon-remove\"></span></button>";
			$table_tr .= "</form></td>\n</tr>\n";
		}
		
		// готовим фрагмент вывода таблицы уже закрытых на данный момент парных сделок	
		$table_header = "Эти сделки пока не торгуются - просто \"черновики\" они и есть черновики...";
		$table_panel = "panel-primary";
		$read_only = "readonly";
		ob_start();
		include("template_table_trades.tpl");
		$table_drafts = ob_get_contents();
		ob_end_clean();
	}
	
} // конец обработки условия if(isNORMAL)

// Выводим сообщения о результатах операций и/или роб ошибках:
$messages = "";
// Область сообщений - они будут "закрывабельны"
$messages .= $trade_opened;
$messages .= $trade_closed;
$messages .= $trade_drafts;
$messages .= $trade_removed;
$messages .= $trade_updated;
$messages .= $multibtn_form_result;
if($messages != "") {
	// если область сообщений не пуста - выводим только их и после них кнопку, ведущую опять на домашнюю страницу этого проекта
	print $messages;
	print "<a href=\"\\\" class=\"btn btn-primary active\" role=\"button\">Продолжаем!</a>";
} else {
	// А вот если сообщений больше нет - выводим все остальное
	// Вот теперь можно выводить основную часть нашей страницы - body:
	include_once("template_body.tpl");
}

// завершаем формирование страницы... то есть выводим футер:
include_once("template_footer.tpl");
?>