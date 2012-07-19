<div id="main-slider" class="slider" ><input id="main-range" type="text" name="cminmax" value="$val" /></div>
<script>
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '$intimate', '|', '$friends', '|', '$coworkers', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ], onstatechange: function(v) { 
	var carr = v.split(";"); 
	network_cmin = carr[0]; 
	network_cmax = carr[1];
	networkRefresh();
 } });

	var slideTimer = null;
	function networkRefresh() {
		if((document.readyState !== "complete") || (slideTimer !== null))
			return;
		slideTimer = setTimeout(networkTimerRefresh,5000);
	}

	function networkTimerRefresh() {
		window.location.href = buildCmd();
	}
</script>
