<?php
set_time_limit(0);

// Max urls per file: 50.000
// Max file size: 10 MB
// changefreq: never, anual, monthly, weekly, daily, hourly, always
// url(length): 12 ~ 2048
// priority: 0.0 ~ 1.0 (Default: 0.5)
// lastmod: The date the document was last modified. The date must conform to the W3C DATETIME format (http://www.w3.org/TR/NOTE-datetime).
//          Example: 2005-05-10 Lastmod may also contain a timestamp. Example: 2005-05-10T17:33:30+08:00

function AddXML($sitemap, $text, $opt="a+")
{
	if($sitemap!=="")$sitemap="-".$sitemap;
	$fp = fopen("..\\sitemap".$sitemap.".xml", $opt);
	//$fp = fopen("sitemap".$sitemap.".xml", $opt);
	$text = str_replace("\\n", "\x0D\x0A", $text);
	fwrite($fp, $text."\x0D\x0A");
	fclose($fp);
}

function AddHeader($sitemap){
	AddXML($sitemap,
			'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
			"w+");
}

function AddHeader_image($sitemap){
	AddXML($sitemap,
			'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
			"w+");
}

function AddHeader_index($sitemap){
	AddXML($sitemap,
			'<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
			"w+");
}

function AddURL($sitemap, $url, $changefreq="", $priority=""){
	if(strlen($url) > 2048)return;

				       $add  = chr(9).'<url>';
				       $add .= '\n'.chr(9).chr(9).'<loc>'.$url.'</loc>';
	if($changefreq!="")$add .= '\n'.chr(9).chr(9).'<changefreq>'.$changefreq.'</changefreq>';
	if($priority!="")  $add .= '\n'.chr(9).chr(9).'<priority>'.$priority.'</priority>';

	AddXML($sitemap, $add, "a+");
}
function AddURL_end($sitemap){
	AddXML($sitemap, chr(9).'</url>', "a+");
}

function AddImg($sitemap, $url, $lastmod, $title){
	if(strlen($url) > 2048)return;

				    $add  = chr(9).chr(9).'<image:image>\n';
				    $add .= chr(9).chr(9).chr(9).'<image:loc>'.$url.'</image:loc>\n';
	if($lastmod!="")$add .= chr(9).chr(9).chr(9).'<image:lastmod>'.$lastmod.'</image:lastmod>\n';
	  if($title!="")$add .= chr(9).chr(9).chr(9).'<image:title>'.$title.'</image:title>\n';
				    $add .= chr(9).chr(9).'</image:image>';

	AddXML($sitemap, $add, "a+");
}

function AddSitemap($sitemap, $url, $lastmod=""){
	$add  = chr(9).'<sitemap>';
	$add .= '\n'.chr(9).chr(9).'<loc>'.$url.'</loc>';
	$add .= '\n'.chr(9).chr(9).'<lastmod>'.$lastmod.'</lastmod>';
	$add .= chr(9).'</sitemap>';
	AddXML($sitemap, $add, "a+");
}

function AddFooter($sitemap){
	AddXML($sitemap, '</urlset>', "a+");
}

function AddFooter_index($sitemap){
	AddXML($sitemap, '</sitemapindex>', "a+");
}

//----------------------------------------

$urls 			= json_decode($_POST['pages']); //[{url,read}]
$imgs 			= json_decode($_POST['images']); //[{url,title,modified}]
//$docs 			= json_decode($_POST['docs']); //[{url,read}]
$REQUEST_SCHEME = $_SERVER["SERVER_PORT"]==443?"https":"http";
$url 			= $REQUEST_SCHEME."://".$_SERVER["HTTP_HOST"];

$sitemap_link 	= 0;
$sitemap_image 	= 0;

/*
** Delete old xml
*/
foreach(glob("..\\sitemap*.xml") as $filename){
    unlink($filename);
}

/*
** Link Sitemap
*/
$e=0;
for($i=0; $i<sizeof($urls); $i++){
	if(($e>0) && ($e%50000)==0){
		AddFooter($sitemap_link);
		$sitemap_link++;
		AddHeader($sitemap_link);
	}
	if($e==0)AddHeader($sitemap_link);
	$e++;

	// Priority
	$priority = 10;
	$priority = $priority-substr_count($urls[$i][0], '/');
	if($priority<5)$priority=5;
	$priority = $priority/10;

	// Change
	$changeArr  = array('daily','weekly','monthly');
	$temp_change = substr_count($urls[$i][0],'/')>sizeof($changeArr)?(sizeof($changeArr)-1):(substr_count($urls[$i][0],'/')-1);
	$changefreq = $changeArr[$temp_change];

	$urls[$i][0] = str_replace("&","&amp;", str_replace("&amp;","&",$urls[$i][0]));
	$urls[$i][0] = str_replace("'","&apos;", $urls[$i][0]);
	$urls[$i][0] = str_replace('"',"&quot;", $urls[$i][0]);
	$urls[$i][0] = str_replace(">","&gt", $urls[$i][0]);
	$urls[$i][0] = str_replace("<","&lt;", $urls[$i][0]);

	AddURL($sitemap_link, $url.$urls[$i][0], $changefreq, $priority);
	AddURL_end($sitemap_link);
}
AddFooter($sitemap_link);

/*
** Image Sitemap
*/
$e=0;
$c="";
for($i=0; $i<sizeof($imgs); $i++){
	if(($e>0) && ($e%50000)==0){
		AddFooter("img-".$sitemap_image);
		$sitemap_image++;
		AddHeader_image("img-".$sitemap_image);
		$e=0;
		$c="";
	}
	if($e==0)AddHeader_image("img-".$sitemap_image);
	$e++;

	// Priority
	$priority = 10;
	$priority = $priority-substr_count($imgs[$i][0], '/');
	if($priority<5)$priority=5;
	$priority = $priority/10;

	// Change
	$changeArr  = array('daily','weekly','monthly');
	$temp_change = substr_count($imgs[$i][0],'/')>sizeof($changeArr)?(sizeof($changeArr)-1):(substr_count($imgs[$i][0],'/')-1);
	$changefreq = $changeArr[$temp_change];

	$imgs[$i][0] = str_replace("&","&amp;", str_replace("&amp;","&",$imgs[$i][0]));
	$imgs[$i][0] = str_replace("'","&apos;", $imgs[$i][0]);
	$imgs[$i][0] = str_replace('"',"&quot;", $imgs[$i][0]);
	$imgs[$i][0] = str_replace(">","&gt", $imgs[$i][0]);
	$imgs[$i][0] = str_replace("<","&lt;", $imgs[$i][0]);

	if($c != $imgs[$i][3]){
		if($e>1)AddURL_end("img-".$sitemap_image);
		AddURL("img-".$sitemap_image, $url.$imgs[$i][3], $changefreq, $priority);
		$c = $imgs[$i][3];
	}
	
	$title = $imgs[$i][1];
	$title = str_replace("&","&amp;", str_replace("&amp;","&",$title));
	$title = str_replace("'","&apos;", $title);
	$title = str_replace('"',"&quot;", $title);
	$title = str_replace(">","&gt", $title);
	$title = str_replace("<","&lt;", $title);

	AddImg("img-".$sitemap_image, $url.$imgs[$i][0], $imgs[$i][2], $title);
	
	if($i==(sizeof($imgs)-1)){
		AddURL_end("img-".$sitemap_image);
	}
}
AddFooter("img-".$sitemap_image);

/*
** Main Sitemap
*/
AddHeader_index("");
for($i=0; $i<=$sitemap_link; $i++){
	AddSitemap("", $url."/sitemap-".$i.".xml", date("Y-m-d"));
}
for($i=0; $i<=$sitemap_image; $i++){
	AddSitemap("", $url."/sitemap-img-".$i.".xml", date("Y-m-d"));
}
AddFooter_index("");

echo "Finish!";
?>
