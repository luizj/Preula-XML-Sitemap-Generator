<?php

function AddXML($file, $text, $opt="a+")
{
	$fp = fopen($file.".xml", $opt);
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
	$url = str_replace("&","&amp;", str_replace("&amp;","&",$url));
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
$url 		= $_POST['url'];
$sitemap 	= "sitemap-0";

for($i=0; $i<sizeof($urls); $i++){
	if($i==0)AddHeader($sitemap);
	// Changes: monthly, weekly, daily
	AddURL($sitemap, $url.$urls[$i][0], "weekly", "1.0");
	//echo $i."-".$urls[$i][0]."<br>";
}
AddFooter($sitemap);

echo "Finish!";
?>
