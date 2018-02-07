<?php
$url = $_SERVER["SERVER_PORT"]==443?"https":"http";;
$url .= "://".$_SERVER["HTTP_HOST"];
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<title>Preula Sitemap Generator</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="jquery-3.3.1.min.js" type="text/javascript"></script>
<script type="text/javascript">

var pages=[["/",'not_read',[]]]; //[url,read,array_images]
var images=['/']; //[url]
var process=[];
var max_process=10;
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
        if(tmp_notreads==0 && tmp_reads>1){
			pause();
			$('#btn_start').prop('disabled', true);
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

	$("#info").html(url);
	$.ajax({
		url: "scan.php",
		dataType: "json",
		async: true,
		type: "POST",
		data: {d:url},
		timeout: 15000,
		success: function(d){
			var temp=[]; //[{url,read,{image_url,title,last-modified]}]
			$.each(d, function(key,val){
				if(val.f==1){
					if($.inArray(val.u, images)==-1){
						images.push(val.u);
						temp = [val.u, val.t, val.m];
						pages[j][2].push(temp);
					}
				}
				else if(val=="/" || val.u=="/"){/*return;*/}
				else if(val.u == undefined || val.u[0]!="/"){
					$("#error").append(url+" ("+val+")("+val.u+")<br>");
				}
				else if($.grep(pages, function(a){return a[0]===val.u;}).length === 0){
					temp = [val.u, 'not_read', []];
					pages.push(temp);
				}
			});
        	pages[j][1] = 'read';
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
		data: {data:JSON.stringify(pages)},
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
