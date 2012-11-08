<div id="contact-slider" class="slider" style="height: 32px; position: relative; left: 5%; width: 90%;"><input id="contact-range" type="text" name="fake-closeness" value="$val" /></div>
<script>
	$("#contact-range").slider({ from: 0, to: 99, step: 1, scale: ['$me', '|', '$intimate', '|', '$friends', '|', '$oldfriends', '|', '$acquaintances', '|', '$world' ], onstatechange: function(v) { $("#contact-closeness-mirror").val(v); }  });
</script>
