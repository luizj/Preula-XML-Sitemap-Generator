<?php

// Max urls per file: 50.000
// changefreq: never, anual, monthly, weekly, daily, hourly, always
// url(length): 12 ~ 2048
// priority: 0.0 ~ 1.0 (Default: 0.5)
// lastmod: The date the document was last modified. The date must conform to the W3C DATETIME format (http://www.w3.org/TR/NOTE-datetime).
//          Example: 2005-05-10 Lastmod may also contain a timestamp. Example: 2005-05-10T17:33:30+08:00

function AddXML($sitemap, $text, $opt="a+")
{
	$fp = fopen("sitemap-".$sitemap.".xml", $opt);
	$text = str_replace("\\n", "\x0D\x0A", $text);
	fwrite($fp, $text."\x0D\x0A");
	fclose($fp);
}

function AddHeader($sitemap){
	AddXML($sitemap,
			'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">',
			"w+");
}

function AddURL($sitemap, $url, $changefreq, $priority){
	if(strlen($url) > 2048)return;

	AddXML($sitemap,
			'  <url>
    <loc>'.$url.'</loc>
    <changefreq>'.$changefreq.'</changefreq>
    <priority>'.$priority.'</priority>
  </url>',
			"a+");
}

function AddFooter($sitemap){
	AddXML($sitemap,
			'</urlset>',
			"a+");
}

//----------------------------------------

$urls 		= json_decode($_POST['data']);
$REQUEST_SCHEME = $_SERVER["SERVER_PORT"]==443?"https":"http";
$url 		= $REQUEST_SCHEME."://".$_SERVER["HTTP_HOST"];
$sitemap 	= 0;

for($i=0; $i<sizeof($urls); $i++){
	if(($i>0) && ($i%50000)==0){
		AddFooter($sitemap);
		$sitemap++;
		AddHeader($sitemap);
	}
	if($i==0)AddHeader($sitemap);

	// Priority
	$priority = 10;
	$priority = $priority-substr_count($urls[$i][0], '/');
	if($priority<5)$priority=5;
	$priority = $priority/10;

	// Change
	$changeArr  = ['daily','weekly','monthly'];
	$temp_change = substr_count($urls[$i][0],'/')>sizeof($changeArr)?(sizeof($changeArr)-1):(substr_count($urls[$i][0],'/')-1);
	$changefreq = $changeArr[substr_count($urls[$i][0],'/')-1];

	$urls[$i][0] = str_replace("&","&amp;", str_replace("&amp;","&",$urls[$i][0]));
	$urls[$i][0] = str_replace("'","&apos;", $urls[$i][0]);
	$urls[$i][0] = str_replace('"',"&quot;", $urls[$i][0]);
	$urls[$i][0] = str_replace(">","&gt", $urls[$i][0]);
	$urls[$i][0] = str_replace("<","&lt;", $urls[$i][0]);

	AddURL($sitemap, $url.$urls[$i][0], $changefreq, $priority);
}
AddFooter($sitemap);

echo "Finish!";
?>
