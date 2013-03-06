<div id="main-slider" class="slider" ><input id="main-range" type="text" name="cminmax" value="$val" /></div>
<script>
	var old_cmin = 0;
	var old_cmax = 99;
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '|', '$intimate', '|', '$friends', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ], onstatechange: function(v) { 
		var carr = v.split(";");
		if(carr[0] != bParam_cmin) {
			old_cmin = bParam_cmin;
	 		bParam_cmin = carr[0];
		}
		if(carr[1] != bParam_cmax) {
			old_cmax = bParam_cmax; 
			bParam_cmax = carr[1];
		}
		networkRefresh();
 	} });

	var slideTimer = null;
	function networkRefresh() {
		if((document.readyState !== "complete") || (slideTimer !== null))
			return;
		if((bParam_cmin == old_cmin) && (bParam_cmax == old_cmax))
			return;
		setTimeout(function() { $("#profile-jot-text-loading").show(); }, 1000 );
		slideTimer = setTimeout(networkTimerRefresh,2000);
	}

	function networkTimerRefresh() {
		slideTimer = null;
		page_load = true;
		liveUpdate();
	}
</script>
