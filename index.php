<?php
	//error_reporting(0);
	require './_admin/init.php';
	//¶ÁÈ¡ÅäÖÃÐÅÏ¢
	$config  = d('config')->get();
	
	//µ±Ç°¸ùurl
	$rootUrl = 'http://'.$_SERVER['HTTP_HOST'].siteUri();
	$snoopy = new Snoopy();
	$uri = substr($_SERVER['REQUEST_URI'],strlen(siteUri()));
	//Æ¥Åä×Ô¶¨ÒåÒ³Ãæ£¬ºÏ²¢²ÎÊý
	foreach($config['pages'] as $page){
		if(@ereg($page['uri'],$uri)){
			if(!empty($page['replaces'])){
				$config['replaces'] = array_merge($config['replaces'],$page['replaces']);
			}
			if(!empty($page['host'])){
				$uri = substr($uri,strlen(dirname($page['uri']))+1);
			}else{
				unset($page['host']);
			}
			unset($config['pages']);
			$config = array_merge($config,$page);
			break;
		}
	}
	//»ñÈ¡ÒªÇëÇóµÄurl
	$url = $config['host'].$uri;
	//µ±Ç°ÇëÇóµÄÎÄ¼þºó×º
	$thisExt = pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION);
	//¾²Ì¬ÎÄ¼þ
	if(in_array($thisExt,explode("|",$config['diyStatic']))){
		$filename = dirname(ADIR).'/'.substr($_SERVER['REDIRECT_URL'],strlen(siteUri()));
		//Èç¹û´æÔÚ£¬Ö±½ÓÊä³ö
		if(is_file($filename)){
			echo file_get_contents($filename);
			exit();
		}
	}
//-------------ÉèÖÃÇëÇóÍ·ÐÅÏ¢------------
	//ÉèÖÃcookie
	switch($config['cookies']){
		case 1://È«¾Öcookies
			$snoopy->cookies = get_cache('cookies');
			break;
		case 2://×Ô¶¨ÒåCOOKIES
			$snoopy->cookies = $config['diyCookies'];
			break;
		default://´«Í³cookies
			$snoopy->cookies = $_COOKIE;
			break;
	}
	
	//ÉèÖÃagent
	switch($config['agent']){
		case 1://²»Î±Ôì
			break;
		case 2://×Ô¶¨Òåagent
			$snoopy->agent = $config['diyAgent'];
			break;
		default://Ê¹ÓÃ¿Í»§¶Ëagent
			$snoopy->agent = $_SERVER['HTTP_USER_AGENT'];
			break;
	}
	
	
	//ÉèÖÃreferer
	switch($config['referer']){
		case 1://×Ô¶¨Òåreferer
			$snoopy->referer = $config['diyReferer'];;
			break;
		default://×Ô¶¯Î±Ôì
			$snoopy->referer = str_replace($rootUrl,$config['host'],$_SERVER['HTTP_REFERER']);
			if($snoopy->referer==$_SERVER['HTTP_REFERER'])
			$snoopy->referer = '';
			break;
	}
	
	//ÉèÖÃip
	switch($config['ip']){
		case 1://Ê¹ÓÃ¿Í»§¶Ëip
			$snoopy->rawheaders["X_FORWARDED_FOR"] = get_ip(); //Î±×°ip 
			break;
		case 2://×Ô¶¨Òåip
			$snoopy->referer = $config['diyReferer'];;
			break;
		default://Ê¹ÓÃ·þÎñÆ÷ip
			break;
	}
	
	//-------ÆäËûÍ·ÐÅÏ¢ begin--
	
	//-------ÆäËûÍ·ÐÅÏ¢ end----
	
	//ÊÇ·ñ²¹È«Á´½Ó
	$snoopy->expandlinks = true;
	
//--------------×¥È¡ÍøÒ³-----------------
	//ÅÐ¶ÏÊÇPOST»¹ÊÇGET
	
	if($_SERVER['REQUEST_METHOD']=="POST"){
		$snoopy->submit($url,$_POST);
	}else{
		$snoopy->fetch($url);
	}
//---------------´¦Àí·µ»ØÐÅÏ¢------------
		//ÉèÖÃcookie
	switch($config['cookies']){
		case 1://È«¾Öcookies
			$snoopy->cookies = set_cache('cookies');
			break;
		default:
			break;
	}
	$contentType = send_header($snoopy->headers);
	$charset = empty($contentType[1])?'utf-8':$contentType[1];
	$charset = trim($charset,"\n\r");
	
	//Ìæ»»ÓòÃû relativeHTML relativeCSS
	if(empty($config['replaceDomain'])){
		if(in_array($thisExt,array('','php','html'))){
			//Ìæ»»ÓòÃû
			$snoopy->results = str_replace($config['host'],$rootUrl,$snoopy->results);
		}
	}
	
	//Ìæ»»Ïà¶ÔµØÖ·relativeHTML
	if(empty($config['replaceDomain'])){
		if(in_array($thisExt,array('','php','html'))){
			$snoopy->results = str_replace('="/','="'.siteUri(),$snoopy->results);
			$snoopy->results = str_replace('=\'/','=\''.siteUri(),$snoopy->results);
			$snoopy->results = preg_replace('/<base href=.*?\/>/','',$snoopy->results);
		}
	}
	
	//Ìæ»»CSSÏà¶ÔµØÖ·
	if(empty($config['relativeCSS'])){
		if(in_array($thisExt,array('css'))){
			$snoopy->results = str_replace('url("/','url("'.siteUri(),$snoopy->results);
		}
	}
	
	//ÄÚÈÝÌæ»»
	if(is_array($config['replaces'])&&!empty($config['replaces']))
	
	foreach($config['replaces'] as $replace){
		$seach = addcslashes(iconv("gb2312",$charset,v($replace['seach'])),'/');
		$replace = iconv("GB2312",$charset,v($replace['replace']));
		$snoopy->results = preg_replace('/'.$seach.'/',$replace,$snoopy->results);
	}
	
	//Ä£°æ
	if(!empty($config['template'])){
		@include(ADIR.'data/tpl/'.$config['template']);
		exit();
	}
	//¾²Ì¬ÎÄ¼þ
	if(in_array($thisExt,explode("|",$config['diyStatic']))){
		$filename = dirname(ADIR).'/'.substr($_SERVER['REDIRECT_URL'],strlen(siteUri()));
		save_file($filename,$snoopy->results);
	}
	
	//Êä³ö
	echo $snoopy->results;
	//echo htmlspecialchars($snoopy->results);