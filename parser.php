<?php

function getUrlsFromPage($url, $domain, $test){
	global $title;
	global $html;
        $arr = '';
	
	echo $url."	\n";
	if (preg_match("~.*/[^/]*\.([^/]*)$~", $test, $test2)){
		$test2 = strtolower($test2[1]);
		switch ($test2){
			case 'html' : 
			case 'htm' : 
			case 'php' : 
			case 'php5' : 
			case 'php4' : 
			case 'php3' : 
				break;
			default:
				return arrToString(get_headers($url));
				break;
		}
	}
        $doc = new DOMDocument();
        if(@ $doc->loadHTMLFile($url)) {
		$html = $doc->saveHTML();
                $title =  $doc->getElementsByTagName("title");
		if ($title){
			if ($title = $title->item(0)){
				$title = $title->textContent;
			}
		} else {
			$title = '';
		}
                $urls =  $doc->getElementsByTagName("a");
                if (!$urls){
                        return false;
                } else {
			for ($i = 0; $i < $urls->length; $i++) {
				$u_arr = $urls->item($i)->attributes;
				for ($j = 0; $j < $u_arr->length; $j++) {
					if ($u_arr->item($j)->nodeName == 'href'){
						$pre_url = trim($u_arr->item($j)->textContent);
						$pre_url = preg_replace("/(.*)#.*$/", "\$1",$pre_url);
						$pre_url = preg_replace("~(.*)/$~", "\$1",$pre_url);
						if(preg_match("/^(http)/", $pre_url)){
							$arr[] = $pre_url;
						} else {
							if(preg_match("/^(\/)/", $pre_url)){
								$arr[] = $domain.$pre_url;
							} else {
								$arr[] = $domain.'/'.$pre_url;
							}
						}
					}
				}
			}
		}
		return $arr;
	} else {
		return false;
	}
}

function getArr($filename){
        
$file_array = file($filename);
        foreach ($file_array as $i => $url){
                $file_array[$i] = rtrim($file_array[$i]);
        }
        return $file_array;
}

function connectToDB(){
	$host='localhost'; 
	$database='pars';
	$user='pars';
	$pswd='wgGLtqMf';
	 
	$dbh = mysql_connect($host, $user, $pswd) or die("can`t connect to mysql");
	mysql_select_db($database) or die("can` connect to database");	
	mysql_query("SET NAMES 'utf8'");
	mysql_query("SET CHARACTER_SET_CLIENT=utf8");
	mysql_query("SET CHARACTER_SET_RESULTS=utf8");
}

function getURL($domain){
	$urls = false;
	global $num_domain;
	$num_domain = 0;
	//$table = arrToString($table, 'getURL');
	foreach ($domain as $d){
		$result = mysql_query("select url,id,domain from `parsing` where parsed = '0' and domain = '$d'  limit 2");
		if (mysql_num_rows($result)){
			$num_domain++;
			while ($row = mysql_fetch_array($result)){
				$urls[$d.'/'.$row['url']]['id'] = $row['id'];
				$urls[$d.'/'.$row['url']]['domain'] = $row['domain'];
				$urls[$d.'/'.$row['url']]['url'] = $row['url'];
			}
		}
	}
	return $urls;
}


function addURL($urlFromPage){
	$recals = '';
	foreach($urlFromPage as $list){
		if (preg_match("~http://([^/]*)/*(.*)~", $list, $parsed)){
			$url_arr[$parsed[1]][] = $parsed[2];
		}
	}
	foreach ($url_arr as $domain => $u_url){
	$sql_q = "select id, url from `parsing` where ";	
	foreach($u_url as $i => $sql_part){
		if ($i+1 == count($u_url)){
			$sql_q .= "url = '".mysql_escape_string($sql_part)."';";
		} else {
			$sql_q .= "url = '".mysql_escape_string($sql_part)."' or \n";
		}
	}
	$result = mysql_query($sql_q);
	
	$u_url = array_flip($u_url);
	if (mysql_num_rows($result)){
		while ($row = mysql_fetch_array($result)){
			if ( isset($u_url[$row['url']]) ){
				unset ($u_url[$row['url']]);
				$recals[] = $row['id'];	
			}
		}
	} 
	$u_url = array_flip($u_url);
	$i = 0;
	$j = 0;
	$h = 0;
	$sql_q = "insert into `parsing` (`domain`, `url`) values \n";
	$sql_file = "insert into `other` (`domain`, `url`) values \n";
	$sql_music = "insert into `music` (`domain`, `url`, `format`) values \n";
	foreach($u_url as $sql_part){
		if (preg_match("~.*\.([^/]*)$~", $sql_part, $test2)){
			$test2 = strtolower($test2[1]);
			switch ($test2){
				case 'html' : 
				case 'htm' : 
				case 'php' : 
				case 'php5' : 
				case 'php4' : 
				case 'php3' : 
				$i++;
				$sql_q .= " ('".mysql_escape_string($domain)."', '".mysql_escape_string($sql_part)."'),";
					break;
				case 'mp3':
				case 'ogg':
				case 'amr':
				case 'ape':
				case 'flac':
				case 'm4a':
				case 'mdi':
				case 'midi':
				case 'ram':
				case 'wav':
				case 'la':
				case 'pac':
				case 'm4a':
				case 'ofr':
				case 'rka':
				case 'shn':
				case 'tak':
				case 'wv':
				case 'wma':
				$h++;
				$sql_music .= " ('".mysql_escape_string($domain)."', '".mysql_escape_string($sql_part)."','".mysql_escape_string($test2)."'),";
					break;	
				default:
				$j++;
				$sql_file .= " ('".mysql_escape_string($domain)."', '".mysql_escape_string($sql_part)."'),";
					break;
			}
		} else {
			$i++;
			$sql_q .= " ('".mysql_escape_string($domain)."', '".mysql_escape_string($sql_part)."'),";
		}
	}
	if ($j){
		$sql_file = preg_replace("~(.*),$~", "\$1;", $sql_file);
		mysql_query($sql_file);	
		$id = mysql_insert_id();
		for ($j = 0; $j < count($u_url); $j++){
			$recals[] = 'file_'.$id;
			$id++; 
		}
		echo mysql_error();
	}
	if ($h){
		$sql_music = preg_replace("~(.*),$~", "\$1;", $sql_music);
		mysql_query($sql_music);
		$id = mysql_insert_id();
		for ($h = 0; $h < count($u_url); $h++){
			$recals[] = 'music_'.$id;
			$id++; 
		}
		echo mysql_error();
	}	
	if ($i){
		$sql_q = preg_replace("~(.*),$~", "\$1;", $sql_q);
		mysql_query($sql_q);
		$id = mysql_insert_id();
		for ($i = 0; $i < count($u_url); $i++){
			$recals[] = $id;
			$id++; 
		}
		echo mysql_error();
	}
}
	//echo $sql_q;
	//echo "error2:	";
	//echo mysql_error()."\n";
	return $recals;
}


function checkTable($domain){
	mysql_query("CREATE TABLE IF NOT EXISTS `parsing` (
		`id` int(11) NOT NULL auto_increment,
		`parsed` bool DEFAULT 0,
		`domain` varchar(50) NOT NULL,
		`url` varchar(200) NOT NULL,
		`recalls` longtext,
		`title` tinytext,
		`info` tinytext,
		`html` longtext,
		`time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`));
	");

	mysql_query("CREATE TABLE IF NOT EXISTS `other` (
		`id` int(11) NOT NULL auto_increment,
		`parsed` bool DEFAULT 0,
		`domain` varchar(50) NOT NULL,
		`url` varchar(200) NOT NULL,
		`info` tinytext,
		`time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`));
	");

	mysql_query("CREATE TABLE IF NOT EXISTS `music` (
		`id` int(11) NOT NULL auto_increment,
		`parsed` bool DEFAULT 0,
		`domain` varchar(50) NOT NULL,
		`url` varchar(200) NOT NULL,
		`info` tinytext,
		`format` varchar(10),
		`time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`));
	");

	foreach ($domain as $d){
		$result = mysql_query("select id from `parsing` where `url` = '' and domain = '$d'");
		if (!mysql_num_rows($result)){
			mysql_query("insert into `parsing` set `url` = '' ,  domain = '$d'");
		}
	}
}

function updateURL($recals, $id, $title, $info, $html){
	$sql_q =  "UPDATE  `parsing` SET  `parsed` =  '1',
		`recalls` =  '".mysql_escape_string(arrToString($recals))."',
		`info` =  '".mysql_escape_string(arrToString($info))."',
		`html` =  '".mysql_escape_string(arrToString($html))."',
		`title` =  '".mysql_escape_string($title)."' WHERE  `id` = $id;";
	mysql_query($sql_q);
	
	//echo "error1:	";
	echo mysql_error();
}


function arrToString($arr, $p=''){
	switch($p){
		case 'getURL':
			$r = '';
			foreach ($arr as $i => $str){
				if ($i+1 == count($arr)){
					$r .= "domain = '$str' ";
				} else {
					$r .= "domain = '$str' or ";
				}
			}
			return $r;
			break;
		default:
			if (is_array($arr)){
				$r = '';
				foreach ($arr as $str){
					$r .= $str.",";
				}
       				return $r;
			} else {
				return $arr;
			}	
	}
}



$param = $argv[1];

if (file_exists($param)){
	$domains = getArr("$param");
} else {
	echo "no file"; 
	die;
}
global $title;
global $html;
global $num_domain;

connectToDB();
checkTable($domains);
for(;;){
	$num_domain = 0;
	$urls = getURL($domains);
	if ($urls){
		foreach($urls as $url){
			$title = '';
			$html = '';
			$urlsFromPage =  getUrlsFromPage('http://'.$url['domain'].'/'.$url['url'], 'http://'.$url['domain'], $url['url']);
			$info = '';
			$recals = '';
			if ($urlsFromPage){
				if (is_array($urlsFromPage)){
					$urlsFromPage = array_unique($urlsFromPage);
					$recals = addURL($urlsFromPage, $url['domain'], $url['url']);
				} else {
					$info = $urlsFromPage;
				}
			} 
			updateURL($recals, $url['id'], $title, $info, $html);
			if ($num_domain <= 3){
				sleep(1);
			}
		}
	} else {
		break;
	}
	
}
echo "et all\n";
?>
