<div id="slider" style="height: 32px; position: relative; left: 5%; width: 90%;"><input id="main-range" type="text" name="cminmax" value="0;99" /></div>
<script>
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '$intimate', '|', '$friends', '|', '$coworkers', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ], onstatechange: function(v) { 
	var carr = v.split(";"); 
	network_cmin = carr[0]; 
	network_cmax = carr[1];
	var newcmd = buildCmd();
	var f;
 } });
</script>
