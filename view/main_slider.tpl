<div id="slider" style="height: 32px; position: relative; left: 5%; width: 90%;"><input id="main-range" type="slider" name="closeness" value="0;99" /></div>
<script>
	$("#main-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '$intimate', '|', '$friends', '|', '$coworkers', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ] });
</script>
