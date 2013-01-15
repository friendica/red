<div id="main-slider" class="slider" ><input id="main-range" type="text" name="cminmax" value="{{$val}}" /></div>
<script>
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['{{$me}}', '|', '{{$intimate}}', '|', '{{$friends}}', '|', '{{$oldfriends}}', '|', '{{$acquaintances}}', '|', '{{$world}}' ], onstatechange: function(v) { 
	var carr = v.split(";"); 
	bParam_cmin = carr[0]; 
	bParam_cmax = carr[1];
	networkRefresh();
 } });

	var slideTimer = null;
	function networkRefresh() {
		if((document.readyState !== "complete") || (slideTimer !== null))
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
