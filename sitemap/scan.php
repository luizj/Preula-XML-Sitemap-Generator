<?php
header('Content-Type: application/json');
set_time_limit(0);

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

function is_image($url){
	if(preg_match("!\.(?:jpe?g|png|gif|xls|doc|rar|zip|exe|txt|bmp|ttf|pdf)!Ui", $url))return true;
	return false;
}

function parseHeaders($headers){
    $head = array();
    foreach($headers as $k=>$v){
        $t = explode(':',$v,2);
        if(isset($t[1])){
        	$head[trim($t[0])] = trim($t[1]);
        }else{
            $head[] = $v;
            if(preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#",$v,$out))$head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}

function str_to_utf8($str){
    if (mb_detect_encoding($str, 'UTF-8', true) === false){
       $str = utf8_encode($str);
    }
    return $str;
}

/*function is_sub_folder($url){
	if(strpos($url,"../")>=0)return true;
	return false;
}*/

function preg_links($url_, $matches){
	global $REQUEST_SCHEME;

    $url = $REQUEST_SCHEME."://".$_SERVER["HTTP_HOST"];

	$ret = array();
	$i=-1;
	foreach($matches as $match){//2=link address, 3=link text
		$i++;

		$match[2] = htmlspecialchars_decode($match[2]);
		if(strpos($match[2],"'")===0 || strpos($match[2],'"')===0)$match[2]=substr($match[2], 1);

		if(is_script($match[2])){
			continue;
		}
		if(is_crap($match[2])){
			continue;
		}
		if(is_external($match[2])){
			//find nofollow
			if(strpos($match[0],"<a")===0 && !preg_match("#rel\s*=\s*['\"\s*]nofollow[\s*'\"]#", $match[0])){
				$ret[$i]['u'] = "Error: Rel Tag Without Nofollow [".htmlspecialchars($match[0])."]";
			}
			continue;
		}else{
			if(strpos($match[0],"<a")===0 && preg_match("#rel\s*=\s*['\"\s*]nofollow[\s*'\"]#", $match[0])){
				continue;
			}
		}
		
		if(is_full_url($match[2])){
			$match[2] = str_replace(array("http:", "https:", "//".$_SERVER["HTTP_HOST"]."/", "//".$_SERVER["HTTP_HOST"]), "", $match[2]);
			$ret[$i]['u'] = "/".$match[2];
		}
		else if(is_internal($match[2])){
			$ret[$i]['u'] = $match[2];
		}
		/*else if(is_sub_folder($match[2])){
			$ret[$i]['u'] = "Error: Invalid Sub Folder";//$match[2];
		}*/
		else if(is_querystring($match[2])){
			if(strpos($url_,"?")>=0){
				$ex = explode("?",$url_);
				$url_=$ex[0];
			}
			$ret[$i]['u'] = $url_.$match[2];
		}
		else if(is_aditional($match[2])){
			$ex = explode("/",$url_);
			$tmp_url_="/";
			for($e=1; $e<(sizeof($ex)-1);$e++){
				$tmp_url_ .= $ex[$e]."/";
			}
			$ret[$i]['u'] = $tmp_url_.$match[2];
		}else{
			$ret[$i]['u'] = "Error: Invalid URL";
		}

		$ret[$i]['f'] = 0;
		if(is_image($match[2])){
			$ret[$i]['f'] = 1;
			$ret[$i]['t'] = strip_tags($match[3]);
			if(preg_match("#alt\s*=\s*['\"](.*?)['\"]#i", $match[0], $alt)){
				$ret[$i]['t'] = str_to_utf8($alt[1]);
			}

			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);
			$ret[$i]['m'] = ""; //blank for this sitemap definition
			/*$data = @file_get_contents($url.$ret[$i]['u'], false, stream_context_create($arrContextOptions));
			if($data){
				$headers = parseHeaders($http_response_header);
		    	if(isset($headers['Last-Modified'])){
		    		$ret[$i]['m'] = date("Y-m-d",strtotime($headers['Last-Modified']));
		    	}
			}*/
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
    $data = @file_get_contents($url, false, stream_context_create($arrContextOptions)) or die('["Error: 404 Not Exists"]');

	$ret = array();
	if(preg_match_all("/<a\s[^>]*href=([\"\']??)([^\\1 >]*?)\\1[^>]*>(.*)<\/a>/simU", $data, $matches, PREG_SET_ORDER)){
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}
	if(preg_match_all("/<img\s[^>]*src=([\"\']??)([^\\1 >]*?)\\1[^>]*>/simU", $data, $matches, PREG_SET_ORDER)){
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}
	if(preg_match_all("/<frame\s[^>]*src=([\"\']??)([^\\1 >]*?)\\1[^>]*>/simU", $data, $matches, PREG_SET_ORDER)){
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}
	if(preg_match_all("/<iframe\s[^>]*src=([\"\']??)([^\\1 >]*?)\\1[^>]*>/simU", $data, $matches, PREG_SET_ORDER)){
		$tmp_ret = preg_links($url_, $matches);
		if(sizeof($tmp_ret)>0)$ret=array_merge($ret, $tmp_ret);
	}

	if(sizeof($ret) == 0){
		$ret = array("/");
		//return '["Error: Zero Links"]';
	}

    $ret = json_encode($ret);
    if(!$ret)return '["Error: JSON Error('.$url_.')"]';
    return $ret;
}

echo get_url($_POST['d']);
?>
