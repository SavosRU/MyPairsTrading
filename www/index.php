<?php
//phpinfo();
error_reporting(E_ALL);

include("MyDB.class.php");

define ("SQL_HOST",		"localhost");
define ("SQL_LOGIN",	"root");
define ("SQL_PWD",		"");
define ("SQL_BASE",		"pairs_trading");

define ("EOL",			"<br />\n");

//define("DEBUG", 0);
//define("isGET", 0);
//define("isPOST", 0);
	
$db = new MyDB(SQL_HOST, SQL_LOGIN, SQL_PWD, SQL_BASE);

// Попытка противостоять постоянному кешированию страницы - во время разработки это крайне неудобно!!!
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-cache, must-revalidate");
  header("Cache-Control: post-check=0,pre-check=0", false);
  header("Cache-Control: max-age=0", false);
  header("Pragma: no-cache");
  
// также на всякий случай передаем браузеру хедер, сообщающий используемую нами кодировку
header('Content-Type: text/html; charset=utf-8');
  
//начинаем формировать страницу... сначала заголовок:
include_once("template_header.tpl");

//Проверяем, есть ли сейчас хоть одна запись в таблице текущих трейдов
$data = $db->query("SELECT * FROM `virtual`");
// И если есть - выводим таблицу, а если нет - выводим форму добавления нового трейда
if($data == NULL) {
	//выводим форму добавления нового парного трейда
	include("template_form_newtrade.tpl");
} else {
	print "YES";
}

//Теперь проверяем был ли при вызове скрипта использован какой-либо POST-запрос (ну или GET-запрос с параметрами)
if (isset($_GET['action'])) {
    // Если запрос происходит через GET-метод, то скорее всего это тестинг... Значит можно печатать диагностические сообщения!
    print "<hr><b><font color=\"red\">DEBUG MODE!</font></b><hr>";
    define("DEBUG", 1);
    define("isGET", 1);
    define("isPOST", 0);
} elseif (isset($_POST['action'])) {
    // Если запрос происходит через POST-метод, то скорее всего это работа... Значит можно НЕ печатать диагностические сообщения!
    // Ведь лишняя диагностика нам только помешает
    print "<hr><b><font color=\"red\">SILENT MODE!</font></b><hr>";
    define("DEBUG", 0);
    define("isGET", 0);
    define("isPOST", 1);
} else {
    // в этом случае - явно просто заход на index.php и нам вообще особо выводить нечего
    define("DEBUG", 0);
    define("isGET", 0);
    define("isPOST", 0);
}

if(isGET OR isPOST) {
	$action = $_REQUEST['action'];
	// если у нас имеется GET-запрос или POST-запрос - проверяем значения полей
	// action у нас по умолчанию может принимать только следующие значения:
	// -> "add_trade" - открыть новый парный трейд
	// -> "del_trade" - завершить имеющийся парный трейд
	// -> "mod_trade" - модифицировать имеющийся парный трейд
	switch ($action) {
		case "add_trade":
			if(DEBUG) print "Open New Trade!".EOL;
			//print_r($_REQUEST);
			
			$t1 = strtoupper(trim($_REQUEST['t1']));
			if(DEBUG) print "Первый тикер: <b>".$t1."</b>".EOL;
			$t2 = strtoupper(trim($_REQUEST['t2']));
    		if(DEBUG) print "Второй тикер: <b>".$t2."</b>".EOL;
			
    		$p1 = strval(str_replace(',','.',trim($_REQUEST['p1'])));			
    		if(DEBUG) print "Первая цена: <b>".$p1."</b>".EOL;
    		$p2 = strval(str_replace(',','.',trim($_REQUEST['p2'])));
    		if(DEBUG) print "Вторая цена: <b>".$p2."</b>".EOL;
			
    		$n1 = intval(trim($_REQUEST['n1']));
			if(DEBUG) print "Первое количество: <b>".$n1."</b>".EOL;    		
    		$n2 = intval(trim($_REQUEST['n2']));
			if(DEBUG) print "Второе количество: <b>".$n2."</b>".EOL;
			
			$type = trim($_REQUEST['type']);
			
			$totalPrice = $p1*$n1 + $p2*$n2;
			$myPair = $t1."*".$n1."-".$t2."*".$n2;
			print "Открываем <b>".$type."</b> сделку на паре <b>".$myPair."</b> по цене <b>$".$totalPrice."</b>".EOL;
			
			break;
		case "del_trade":
			if(DEBUG) print "Delete Exists Trade!";
			break;
		case "mod_trade":
			if(DEBUG) print "Modify Exists Trade!";
			break;
		default:
			if(DEBUG) print "Что-то не так с запросом: не верный ACTION!!!";
	}
}



// завершаем формирование страницы... то есть выводим футер:
include_once("template_footer.tpl");
?>