<?php
$url = $_SERVER["SERVER_PORT"]==443?"https":"http";
$url .= "://".$_SERVER["HTTP_HOST"];
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<title>Preula Sitemap Generator</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="jquery-3.3.1.min.js" type="text/javascript"></script>
<script type="text/javascript">

var pages=[["/",'not_read','text/html']]; //[url,read,content-type]
var images=[]; //[url]
var docs=[]; //[url]
var process=[];
var max_process=20;
var timer = 0;
var paused=false;

for(var i=0; i<max_process; i++)process[i] = false;

function print_status(){
	$("#images").html(images.length);
	$("#total").html(pages.length);
	$("#reads").html($.grep(pages,function(a){return a[1]==='read';}).length);
	$("#process").html($.grep(process,function(a){return a===true;}).length);
}
function get_url(){
	if(paused){return;}

	var tmp_notreads = $.grep(pages,function(a){return a[1]==='not_read';}).length;
	
	var id_process = process.indexOf(false);
	if(id_process >= 0 && tmp_notreads>0){
		process[id_process] = true;
		print_status();
    }else{	
		var tmp_reads = $.grep(pages,function(a){return a[1]==='read';}).length;
        if(tmp_notreads==0 && tmp_reads>1 && tmp_reads == pages.length){
			pause();
			$('#btn_start').prop('disabled', true);
			
			//limpa as imgs
			for(var h=(pages.length-1); 0<=h;h--){
				if(pages[h][2].indexOf('text/html') != 0){
					console.log(pages[h]);
					pages.splice(h,1);
				}
			}
			print_status();
			
			create_xml();
		}
		return;
	}
	
	var url="";
	var j=0;
	for(j=0; j<$(pages).length; j++){
		if(pages[j][1] == 'not_read'){
			url = pages[j][0];
			pages[j][1] = 'reading';
			break;
		}
	}

	pages[j][2] = "";
	$("#info").html(url);
	$.ajax({
		url: url,
		dataType: "html",
		async: true,
		timeout: 60000,
		cache: false,
		success: function(response, status, xhr){
			var data=[],data_img=[],data_doc=[];
			pages[j][1] = 'read';
			$($.parseHTML(response)).each(function() {
				pages[j][2] = xhr.getResponseHeader("content-type");
				
				if(xhr.getResponseHeader("content-type").indexOf('text/html') == 0){
					$(this).find("a").each(function() {
						if(!this.hasAttribute('rel') || $(this).attr('rel').indexOf('nofollow') <0){
							if(!this.hasAttribute('href'))
							{
								$("#error").append('Link sem href na pagina: '+url+'<br>');
								return true;
							}
							var ut = $(this).attr("href");
							
							if(ut.indexOf(location.origin)==0){
								ut = ut.replace(location.origin,'');	
							}

							if(ut.indexOf('https://')!=0 && 
							   ut.indexOf('http://')!=0 && 
							   ut.indexOf('#') != 0 && 
							   ut.indexOf('/') != 0 && 
							   ut.indexOf(':') < 0){
								ut = url.substr(0, url.lastIndexOf("/"))+'/'+ut;
							}

							<? if($_SERVER["SERVER_PORT"]==443){ ?>
							console.log(ut.indexOf(':'));
							if(ut.indexOf('http://<?=$_SERVER["HTTP_HOST"]?>')==0 && 
							   ut.indexOf('#') != 0 && 
							   ut.indexOf('/') != 0 && 
							   ut.indexOf(':') < 0){
								$("#error").append('URL linkada sem HTTPS: '+url+' - '+ut+'<br>');
								return true;
							}
							<? } ?>
							
							if(ut.indexOf('<?=$url?>')!=0 && 
							   ut.indexOf('http')==0){
								$("#error").append('URL externa sem nofollow: '+url+' - '+ut+'<br>');
								return true;
							}

							if(ut.indexOf('/') == 0){
								data.push({
										u: ut,
										g: url
								});
							}
						}
					});
					
					$(this).find("img").each(function() {
						var ut = $(this).attr("src");
						var alt = $(this).attr("alt");

						if(ut.indexOf(location.origin)==0){
							ut = ut.replace(location.origin,'');	
						}

						if(ut.indexOf('https://')!=0 && 
						   ut.indexOf('http://')!=0 && 
						   ut.indexOf('#') != 0 && 
						   ut.indexOf('/') != 0 && 
						   ut.indexOf(':') < 0){
							ut = url.substr(0, url.lastIndexOf("/"))+'/'+ut;
						}

						<? if($_SERVER["SERVER_PORT"]==443){ ?>
						if(ut.indexOf('http://<?=$_SERVER["HTTP_HOST"]?>')==0 &&
						   ut.indexOf('/') != 0){
							$("#error").append('IMG linkada sem HTTPS: '+url+' - '+ut+'<br>');
							return true;
						}
						<? } ?>

						if(ut.indexOf('/') == 0){
							data_img.push({
								 u: ut,
								 t: alt,
								 m: '',
								 g: url
							});
						}
					});
				}else if(xhr.getResponseHeader("content-type").indexOf('image') == 0){
				}
			});

			var temp=[]; //[{url,read,{image_url,title,last-modified]}]
			$.each(data, function(key,val){
				if(val=="/" || val.u=="/"){/*return;*/}
				else if(val.u == undefined || val.u[0]!="/"){
					$("#error").append(url+" ("+val+")("+val.u+")<br>");
				}
				else if($.grep(pages, function(a){return a[0]===val.u;}).length === 0 &&
					   $.grep(pages, function(a){return a[0]==='';}).length === 0){
					temp = [val.u, 'not_read', [], val.g];
					pages.push(temp);
				}
			});
			$.each(data_img, function(key,val){
				if($.grep(images, function(a){return a[0]===val.u;}).length === 0){
					images.push([val.u, val.t, val.m, val.g]);
				}
			});
		},
		error: function(d,e,c){
			pages[j][1] = 'not_read';
			console.error('['+e+'] Error URL: '+url+' (Reading again..)');
		},
		complete: function(){
			process[id_process] = false;
			print_status();
		}
	});
}

function start(){
	$('#btn_start').prop('disabled', true);
	$('#btn_pause').prop('disabled', false);
	$('#btn_create').prop('disabled', true);
	paused = false;
	if(timer)window.clearInterval(timer);
	timer = window.setInterval('get_url()', 100);
}

function pause(){
	$("#info").html("Paused");
	$('#btn_start').prop('disabled', false);
	$('#btn_pause').prop('disabled', true);
	$('#btn_create').prop('disabled', false);
	paused = true;
	window.clearTimeout(timer);
	timer = false;
}

function create_xml(){
	$("#info").html('Wait, creating the sitemap.');
	$('#btn_create').prop('disabled', true);

	$.ajax({
		type: "POST",
		url: "save.php",
		data: {pages:JSON.stringify(pages),images:JSON.stringify(images),docs:JSON.stringify(docs)},
		async: true,
		contentType: "application/x-www-form-urlencoded;charset=UTF-8",
		success: function(d){
			$("#info").html(d);
			$('#btn_create').prop('disabled', false);
		},
		error: function(d,e){
			$("#info").html('['+e+'] XML Error');
			$('#btn_create').prop('disabled', false);
		}
	});
}
</script>
</head>

<body>
<h3>Creating Sitemap: <?php echo $url;?></h3>
Total Pages: <label id="total">0</label><br>
Total Images: <label id="images">0</label><br>
Reading Page: <label id="reads">0</label><br>
Process: <label id="process">0</label><br><br>
<button onclick="start();" id="btn_start">Start</button>
<button onclick="pause();" id="btn_pause" disabled>Pause</button>
<button onclick="create_xml();" id="btn_create" disabled>Create XML</button>
<br><br>
Info: <label id="info"></label><br>
<div style="color:red;width:100%;" id="error">Errors:<br></div>
</body>

</html>
