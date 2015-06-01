<?php
$url = $_SERVER["SERVER_PORT"]==443?"https":"http";;
$url .= "://".$_SERVER["HTTP_HOST"];
?>
<!DOCTYPE HTML>
<html lang="pt-br">
<head>
<title>Preula Sitemap Generator</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="jquery-2.1.4.min.js" type="text/javascript"></script>
<script type="text/javascript">

var paginas=[["/",false]];
var processos=[];
var max_processos=100;
var tempo = 0;
var ultima_lida = 0;

for(var i=0; i<max_processos; i++)processos[i] = false;

function IsJsonString(str){
	if(typeof $.parseJSON(str) =='object'){
		return true;
	}else{
		return false;
    }
    return true;
}

function get_url(){
	var id_processo=9999;
	for(var i=0; i<=max_processos; i++)
    {
        if(processos[i] == false){
        	processos[i] = true;
        	id_processo=i;
        	break;
        }
    }
	if(id_processo==9999)return;

	var url="";
	for(var j=ultima_lida; j<$(paginas).length; j++)
    {
    	ultima_lida=j;
        if(paginas[j][1] == false){
        	url= paginas[j][0];
        	paginas[j][1] = true;
        	break;
        }
    }
    if(url=="")return;
    $("#info").html(url);

    $.ajax({
		url: "scan.php?url="+url,
		dataType: "json",
		async: true,
		success: function(data){
			var temp=[]; //[url,lida]
			var found=false;

			$.each(data, function(key,val){
				if(val[0]!="/")return $("#error").append(url+" ("+val+")<br>");

				found=false;
		        for(var k=0; k<paginas.length; k++)
		        {
		            if(paginas[k][0] == val){
		            	found=true;
		            	break;
		            }
		        }
				if(!found){
					temp=[val,false];
					paginas.push(temp);
				}
			});

			$("#total").html(paginas.length);
	        $("#lidas").html((ultima_lida+1));

	        processos[id_processo] = false;
	        var tmp_proc=0;
	        for(var m=0; m<=max_processos; m++)
	        {
	            if(processos[m]==true)tmp_proc++;
	        }
	        $("#processos").html(tmp_proc);

	        /*if(paginas.length>1 && paginas.length==(ultima_lida+1) && tmp_proc==(max_processos-1)){
	        	$("#info").html("Finish");
				$('#btn_start').prop('disabled', false);
				$('#btn_pause').prop('disabled', true);
				$('#btn_gerar').prop('disabled', false);
				window.clearInterval(tempo);
	        }*/
		},
		error: function(d,e){
			$("#info").html('['+e+'] Error URL: '+url);

			processos[id_processo] = false;
	        var tmp_proc=0;
	        for(var m=0; m<=max_processos; m++)
	        {
	            if(processos[m]==true)tmp_proc++;
	        }
	        $("#processos").html(tmp_proc);
		}
	});
}

function start(){
	$('#btn_start').prop('disabled', true);
	$('#btn_pause').prop('disabled', false);
	$('#btn_gerar').prop('disabled', true);
	tempo = window.setInterval('get_url()', 10);
}

function pause(){
	$("#info").html("Paused");
	$('#btn_start').prop('disabled', false);
	$('#btn_pause').prop('disabled', true);
	$('#btn_gerar').prop('disabled', false);
	window.clearInterval(tempo);
}

function gerar_xml(){
	$("#info").html('Wait, creating the sitemap.');
	$('#btn_gerar').prop('disabled', true);
	$.ajax({
		type: "POST",
		url: "save.php",
		data: {data:JSON.stringify(paginas)},
		async: true,
		contentType: "application/x-www-form-urlencoded;charset=UTF-8",
		success: function(d){
			$("#info").html(d);
			$('#btn_gerar').prop('disabled', false);
		},
		error: function(d,e){
			$("#info").html('['+e+'] XML Error');
			$('#btn_gerar').prop('disabled', false);
		}
	});
}
</script>
</head>

<body>
<h3>Creating Sitemap: <?php echo $url;?></h3>
Total Pages: <label id="total">0</label><br>
Reading Page: <label id="lidas">0</label><br>
Process: <label id="processos">0</label><br><br>
<button onclick="start();" id="btn_start">Start</button>
<button onclick="pause();" id="btn_pause" disabled>Pause</button>
<button onclick="gerar_xml();" id="btn_gerar" disabled>Create XML</button>
<br><br>
Info: <label id="info"></label><br>
<div style="color:red;width:100%;" id="error">Errors:<br></div>
</body>

</html>
