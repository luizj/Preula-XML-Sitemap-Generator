<?php
$url = $_SERVER["SERVER_PORT"]==443?"https":"http";;
$url .= "://".$_SERVER["HTTP_HOST"];
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<title>Preula Sitemap Generator</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="jquery-2.1.4.min.js" type="text/javascript"></script>
<script type="text/javascript">

var pages=[["/",false,[]]]; //[url,read,array_images]
var images=['/']; //[url]
var process=[];
var max_process=50;
var timer = 0;
var last_read = 0;
var paused=false;

for(var i=0; i<max_process; i++)process[i] = false;

function get_url(){
	$("#images").html(images.length);
	$("#total").html(pages.length);
    $("#reads").html((last_read+1));

    var tmp_proc = $.grep(process, function(a){return a===true;}).length;
    $("#process").html(tmp_proc);

	if(paused)return;

	var url="";
	var j=last_read;
	for(; j<$(pages).length; j++)
    {
    	last_read=j;
        if(pages[j][1] == false){
        	url = pages[j][0];
        	pages[j][1] = true;
        	break;
        }
    }
	if(url==""){
		if(tmp_proc==0 && pages.length==(last_read+1)){
			pause();
			$('#btn_start').prop('disabled', true);
			gerar_xml();
		}
		return;
	}
	var id_process = process.indexOf(false);
	if(id_process >= 0){
		process[id_process] = true;
    }else{return;}

    $("#info").html(url);
    $.ajax({
		url: "scan.php",
		dataType: "json",
		async: true,
		type: "POST",
		data: {d:url},
		timeout: 1000*600,
		success: function(data){
			process[id_process] = false;

			var temp=[]; //[url,read,array_images]
			$.each(data, function(key,val){
				if(val.f=="1"){
					if($.inArray(val.u, images) >= 0){
						return;
					}
				    images.push(val.u);
					temp = [val.u, val.t, val.m];
					pages[j][2].push(temp);
					return;
				}
				if(val=="/")return;
				if(val.u == undefined || val.u[0]!="/"){
					$("#error").append(url+" ("+val+")("+val.u+")<br>");
					return;
				}
				if(val.u=="/")return;

				if($.grep(pages, function(a){return a[0]===val.u;}).length === 0){
					temp = [val.u, false, []];
					pages.push(temp);
				}
			});
		},
		error: function(d,e){
			console.error('['+e+'] Error URL: '+url);
			process[id_process] = false;
		}
	});
}

function start(){
	$('#btn_start').prop('disabled', true);
	$('#btn_pause').prop('disabled', false);
	$('#btn_create').prop('disabled', true);
	paused = false;
	if(timer)window.clearInterval(timer);
	timer = window.setInterval('get_url()', 30);
}

function pause(){
	$("#info").html("Paused");
	$('#btn_start').prop('disabled', false);
	$('#btn_pause').prop('disabled', true);
	$('#btn_create').prop('disabled', false);
	paused = true;
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
