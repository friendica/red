<div id="main-slider" class="slider" style="height: 32px; position: relative; left: 5%; width: 80%;"><input id="main-range" type="text" name="cminmax" value="$val" /></div>
<input id="slider-refresh" type="submit" name="submit" value="$refresh" onclick="networkRefresh();" /><div class="clear"></div>
<script>
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '$intimate', '|', '$friends', '|', '$coworkers', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ], onstatechange: function(v) { 
	var carr = v.split(";"); 
	network_cmin = carr[0]; 
	network_cmax = carr[1];
 } });

	function networkRefresh() {
		window.location.href = buildCmd();
	}

</script>
