<?php
error_reporting(0);
define("ABS",$_SERVER['REQUEST_SCHEME'].'://'. $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']).'');
// DO NOT EDIT THIS FILE IF YOU'RE NEWBIE IN PHP

function init() {
	if(!file_exists("library/config.php")) {
		echo'Rename <b>library/config.php.sample</b> in library/config.php and retry!';
		exit();
	}
	else {
		include("library/config.php"); 
		databaseLogin(DB_HOST,DB_USER,DB_PASS,DB_NAME);
		if(!mysql_query("SELECT 1 FROM url")) {
			run_install();
		}
		else {
			$ses_id = user_cookie();
			action($ses_id);
			return $ses_id;
		}
	}	
}
function databaseLogin($db_host,$db_user,$db_parola,$db) {   	
	$link=mysql_connect($db_host, $db_user, $db_parola); 
 	if(!$link) die("mySQL server connection problem! Check <b>library/config.php</b>"); 
 	$dbcon=mysql_select_db($db,$link);  
	if (!$dbcon) die("mySQL select database problem!  Check <b>library/config.php</b>");
}

function action($ses) {
	if(@$_GET['act'] == "go") { include("library/go.php"); exit(); } 
	elseif(@$_GET['act'] == "ajax") { include("library/ajax.php"); exit(); }  
	else {}
}	


function run_install() {
	mysql_query("CREATE TABLE IF NOT EXISTS `url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `short` text NOT NULL,
  `cookie` text NOT NULL,
  `date` varchar(20) NOT NULL,
  `hits` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;") or die(mysql_error());
}

function user_cookie() {
	$cookie_name = 'urlsrt';
	if(isset($_COOKIE[$cookie_name])) { $ses_id = $_COOKIE[$cookie_name]; }
	else {
		$value = md5(''.mktime().'-'.$_SERVER['REMOTE_ADDR'].'');
		setcookie($cookie_name, $value, time()+((3600*24)*365)*10);  /* expire in 10 years */
		$ses_id = $value;
	}
	return $ses_id;
}

function my_url($cookie) {
	$arr = array();
	$sql = mysql_query("SELECT id,url,short,hits,`date` FROM url WHERE cookie='$cookie' ORDER BY id DESC") or die(mysql_error());
	if(mysql_num_rows($sql) == 0) {}
	else {
		while($row = mysql_fetch_object($sql)) {
			$date = $row->date;
			$now = mktime();
			$ago = abs(round(($date-$now)/(3600*24)));
		
			$arr[] = array(
				'id'=>$row->id,
				'original_link'=>$row->url,
				'short_link'=>''.ABS.'/'.$row->short,
				'hits'=>$row->hits,
				'days_ago'=>$ago
			);
		}
	}
	mysql_free_result($sql);
	return $arr;
}

function validURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function validCustom($text) {
	if (ereg("[^A-Za-z0-9]", $text)) {
		return false;
	}
	else {
		return true;
	}
}

function genURL($length = "")
{	
	$code = md5(uniqid(rand(), true));
	if ($length != "") return substr($code, 0, $length);
	else return $code;
}

function add_url($url,$short,$type) {
	$cookie = user_cookie();
	$date = mktime();
	
	$short = check_unique($short,$type);
	if($short == "0") {} 
	else {
		mysql_query("INSERT INTO url SET url='$url',short='$short',cookie='$cookie',`date`='$date',hits=0");
	}
	return $short;
}

function check_unique($short,$type) {
	$sql = mysql_query("SELECT id FROM url WHERE short='$short' LIMIT 0,1");
	if(mysql_num_rows($sql) == 0) {}
	else {
		if($type == "custom") {
			$short = 0;
		}
		else {
			$short = genUrl(rand(3,5));
			$short = check_unique($short);
		}
	}
	mysql_free_result($sql);
	return $short;
}

?>