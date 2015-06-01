<?php
header('Content-Type: application/json');

$REQUEST_SCHEME = $_SERVER["SERVER_PORT"]==443?"https":"http";

function is_internal($url){
	if(strpos($url, "/")===0 && strpos($url, "//")===false)return true;
	return false;
}

function is_external($url){
	if(strpos($url, "//")===0)
	{
		if(strpos($url, "//".$_SERVER["HTTP_HOST"])!==0){return true;}
	}
	if(strpos($url, "http://")===0)
	{
		if(strpos($url, "http://".$_SERVER["HTTP_HOST"])!==0){return true;}
	}
	if(strpos($url, "https://")===0)
	{
		if(strpos($url, "https://".$_SERVER["HTTP_HOST"])!==0){return true;}
	}
	return false;
}

function is_full_url($url){
	if(strpos($url, "http://".$_SERVER["HTTP_HOST"])===0 ||
	   strpos($url, "https://".$_SERVER["HTTP_HOST"])===0 ||
	   strpos($url, "//".$_SERVER["HTTP_HOST"])===0)return true;
	return false;
}

function is_script($url){
	if(stripos($url, 'javascript:') === 0 || stripos($url, 'mailto:') === 0)return true;
	return false;
}

function is_querystring($url){
	if(strpos($url,"?")===0)return true;
	return false;
}

function is_aditional($url){
	if(strpos($url,"//")===false)return true;
	return false;
}

function is_crap($url){
	if(strpos($url,"#")===0)return true;
	return false;
}

/*function is_sub_folder($url){
	if(strpos($url,"../")>=0)return true;
	return false;
}*/

function preg_links($url_, $matches){
	$ret = array();
	foreach($matches as $match){//2=link address, 3=link text
		if(preg_match("/(.jpg|.jpeg|.gif|.png|.xls|.doc|.rar|.zip|.exe|.txt|.bmp|.ttf)/i", $match[2]))continue;
		$match[2] = htmlspecialchars_decode($match[2]);

		if(strpos($match[2],"'")===0 || strpos($match[2],'"')===0)$match[2]=substr($match[2], 1);

		if(is_script($match[2])){
			continue;
		}
		if(is_crap($match[2])){
			continue;
		}
		if(is_external($match[2])){
			if(strpos($match[0],"<a")===0 && !preg_match("#rel\s*=\s*['\"]nofollow['\"]#", $match[0])){
				$ret[] = "Error: Rel Tag Without Nofollow [".htmlspecialchars($match[0])."]";//$match[2]
			}
			continue;
		}
		else if(is_full_url($match[2])){
			$match[2] = str_replace(array("http:", "https:", "//".$_SERVER["HTTP_HOST"]."/", "//".$_SERVER["HTTP_HOST"]), "", $match[2]);
			$ret[] = "/".$match[2];
		}
		else if(is_internal($match[2])){
			$ret[] = $match[2];
		}
		/*else if(is_sub_folder($match[2])){
			$ret[] = "Error: Invalid Sub Folder";//$match[2];
		}*/
		else if(is_querystring($match[2])){
			if(strpos($url_,"?")>=0){
				$ex = explode("?",$url_);
				$url_=$ex[0];
			}
			$ret[] = $url_.$match[2];
		}
		else if(is_aditional($match[2])){
			$ex = explode("/",$url_);
			$tmp_url_="/";
			for($i=1; $i<(sizeof($ex)-1);$i++){
				$tmp_url_ .= $ex[$i]."/";
			}
			$ret[] = $tmp_url_.$match[2];
		}else{
			$ret[] = "Error: Invalid URL";//$match[2]
		}
	}
	return $ret;
}

function get_url($url_){
	global $REQUEST_SCHEME;

    $url = $REQUEST_SCHEME."://".$_SERVER["HTTP_HOST"].$url_;
    $arrContextOptions=array(
	    "ssl"=>array(
	        "verify_peer"=>false,
	        "verify_peer_name"=>false,
	    ),
	);

    $url = str_replace(" ", "%20",$url);
    $data = file_get_contents($url, false, stream_context_create($arrContextOptions)) or die('["Error: 404 Not Exists"]');

	$ret = array();
	if(preg_match_all("/<a\s[^>]*href=([\"\']??)([^\\1 >]*?)\\1[^>]*>(.*)<\/a>/siU", $data, $matches, PREG_SET_ORDER)) {
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}
	if(preg_match_all("/<frame\s[^>]*src=([\"\']??)([^\\1 >]*?)\\1[^>]*>/siU", $data, $matches, PREG_SET_ORDER)) {
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}
	if(preg_match_all("/<iframe\s[^>]*src=([\"\']??)([^\\1 >]*?)\\1[^>]*>/siU", $data, $matches, PREG_SET_ORDER)) {
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}

	if(sizeof($ret) == 0){
		$ret = array("/");
	}

    $ret = json_encode($ret);
    if(!$ret)return '["Error: JSON Error"]';
    return $ret;
}

echo get_url($_GET['url']);
?>
