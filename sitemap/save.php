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
	$fp = fopen("sitemap".$sitemap.".xml", $opt);
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

function AddURL($sitemap, $url, $changefreq="", $priority="", $lastmod=""){
	if(strlen($url) > 2048)return;

				       $add  = '    <url>';
				       $add .= '\n       <loc>'.$url.'</loc>';
	if($changefreq!="")$add .= '\n       <changefreq>'.$changefreq.'</changefreq>';
	if($priority!="")  $add .= '\n       <priority>'.$priority.'</priority>';
	if($lastmod!="")   $add .= '\n       <lastmod>'.$lastmod.'</lastmod>';

	AddXML($sitemap, $add, "a+");
}
function AddURL_end($sitemap){
	AddXML($sitemap, '    </url>', "a+");
}

function AddImg($sitemap, $url, $lastmod, $title){
	if(strlen($url) > 2048)return;

				  $add  = '    <image:image>\n';
				  $add .= '       <image:loc>'.$url.'</image:loc>\n';
				//$add .= '       <image:lastmod>'.$lastmod.'</image:lastmod>\n';
	if($title!="")$add .= '       <image:title>'.$title.'</image:title>\n';
				  $add .= '    </image:image>';

	AddXML($sitemap, $add, "a+");
}

function AddFooter($sitemap){
	AddXML($sitemap,
			'</urlset>',
			"a+");
}

//----------------------------------------

$urls 			= json_decode($_POST['data']); //[{url,read,{image_url,title,modified}}]
$REQUEST_SCHEME = $_SERVER["SERVER_PORT"]==443?"https":"http";
$url 			= $REQUEST_SCHEME."://".$_SERVER["HTTP_HOST"];

$sitemap_link 	= 0;
$sitemap_image 	= 0;

/*
** Delete old xml
*/
foreach(glob("sitemap*.xml") as $filename){
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
	$changeArr  = ['daily','weekly','monthly'];
	$temp_change = substr_count($urls[$i][0],'/')>sizeof($changeArr)?(sizeof($changeArr)-1):(substr_count($urls[$i][0],'/')-1);
	$changefreq = $changeArr[$temp_change];

	$urls[$i][0] = str_replace("&","&amp;", str_replace("&amp;","&",$urls[$i][0]));
	$urls[$i][0] = str_replace("'","&apos;", $urls[$i][0]);
	$urls[$i][0] = str_replace('"',"&quot;", $urls[$i][0]);
	$urls[$i][0] = str_replace(">","&gt", $urls[$i][0]);
	$urls[$i][0] = str_replace("<","&lt;", $urls[$i][0]);

	AddURL($sitemap_link, $url.$urls[$i][0], $changefreq, $priority, "");
	AddURL_end($sitemap_link);
}
AddFooter($sitemap_link);

/*
** Image Sitemap
*/
$e=0;
for($i=0; $i<sizeof($urls); $i++){
	if(sizeof($urls[$i][2])==0)continue;

	if(($e>0) && ($e%50000)==0){
		AddFooter("img-".$sitemap_image);
		$sitemap_image++;
		AddHeader_image("img-".$sitemap_image);
	}
	if($e==0)AddHeader_image("img-".$sitemap_image);
	$e++;

	// Priority
	$priority = 10;
	$priority = $priority-substr_count($urls[$i][0], '/');
	if($priority<5)$priority=5;
	$priority = $priority/10;

	// Change
	$changeArr  = ['daily','weekly','monthly'];
	$temp_change = substr_count($urls[$i][0],'/')>sizeof($changeArr)?(sizeof($changeArr)-1):(substr_count($urls[$i][0],'/')-1);
	$changefreq = $changeArr[$temp_change];

	$urls[$i][0] = str_replace("&","&amp;", str_replace("&amp;","&",$urls[$i][0]));
	$urls[$i][0] = str_replace("'","&apos;", $urls[$i][0]);
	$urls[$i][0] = str_replace('"',"&quot;", $urls[$i][0]);
	$urls[$i][0] = str_replace(">","&gt", $urls[$i][0]);
	$urls[$i][0] = str_replace("<","&lt;", $urls[$i][0]);

	AddURL("img-".$sitemap_image, $url.$urls[$i][0], $changefreq, $priority, "");

	// Add Images
	$images = $urls[$i][2];
	for($a=0; $a<sizeof($images); $a++){
			$title = $images[$a][1];
			$title = str_replace("&","&amp;", str_replace("&amp;","&",$title));
			$title = str_replace("'","&apos;", $title);
			$title = str_replace('"',"&quot;", $title);
			$title = str_replace(">","&gt", $title);
			$title = str_replace("<","&lt;", $title);

		AddImg("img-".$sitemap_image, $url.$images[$a][0], $images[$a][2], $title);
	}

	AddURL_end("img-".$sitemap_image);
}
AddFooter("img-".$sitemap_image);

/*
** Main Sitemap
*/
AddHeader("");
for($i=0; $i<=$sitemap_link; $i++){
	AddURL("", $url."/sitemap-".$i.".xml", "", "", date("Y-m-d"));
	AddURL_end("");
}
for($i=0; $i<=$sitemap_image; $i++){
	AddURL("", $url."/sitemap-img-".$i.".xml", "", "", date("Y-m-d"));
	AddURL_end("");
}
AddFooter("");

echo "Finish!";
?>
