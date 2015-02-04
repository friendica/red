function dirdetails(hash) {

	$.get('dirprofile' + '?f=&hash=' + hash, function( data ) {
		$.colorbox({ maxWidth: "50%", maxHeight: "75%", html: data });
	});
}


function doRatings(hash) {

	var html = '<form action="prate" method="post"><input type="hidden" name="target" value="'+hash+'" /><input id="dir-rating-range" class="directory-slider" type="text" value="0" name="rating" style="display: none;" /><input type="text" name="rating-text" class="directory-rating-text" /><input name="submit" type="submit" value="submit" ></form><div class="clear"></div><script>$("#dir-rating-range").jRange({ from: -10, to: 10, step: 1, width:"100%", showLabels: false, showScale: true, scale : [ "-10","-5","0","5","10" ], onstatechange: function(v) { $("#dir-rating-range").val(v); } });</script>';

	$.colorbox({maxwidth: "50%", maxHeight: "50%", html: html });

}


$(document).ready(function() {
	collapseHeight();
});