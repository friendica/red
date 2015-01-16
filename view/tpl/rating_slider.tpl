<div id="rating-slider" class="slider" style="height: 32px; position: relative; left: 5%; width: 90%;"><input id="rating-range" type="text" name="fake-rating" value="{{$val}}" /></div>
<script>
	$("#rating-range").jRange({ from: -10, to: 10, step: 1, width:'100%', showLabels: false, showScale: true, scale : [ '-10','-8','-6','-4','-2','0','2','4','6','8','10' ], onstatechange: function(v) { $("#contact-rating-mirror").val(v); }  });
</script>
