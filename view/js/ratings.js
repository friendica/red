
var ratingVal = 0;
var ratingText = '';
var currentHash = '';

function fetchRatings(hash) {
	$.get('prate/' + hash, function(data) {
		if(typeof(data.rating) !== 'undefined') {
			ratingVal = data.rating;
			ratingText = data.rating_text;
		}
		buildRatingForm(hash);
	});
}

function doRatings(hash) {
	fetchRatings(hash);
}

function buildRatingForm(hash) {
	var html = '<form id="ratings_form" action="prate" method="post"><input type="hidden" name="target" value="' + hash + '" /><div class="rating-desc">' + aStr.rating_desc + '</div><input id="dir-rating-range" class="directory-slider" type="text" value="' + ratingVal + '" name="rating" style="display: none;" /><div class="rating-text-label">' + aStr.rating_text + '<input type="text" name="rating_text" class="directory-rating-text" value="' + ratingText + '" /><br><input name="submit" class="directory-rating-submit" type="submit" value="' + aStr.submit + '" onclick="postRatings(); return false;"></form><div class="clear"></div><script>$("#dir-rating-range").jRange({ from: -10, to: 10, step: 1, showLabels: false, showScale: true, scale: [ "-10","-5","0","5","10" ], onstatechange: function(v) { $("#dir-rating-range").val(v); } });</script>';

	$.colorbox({maxwidth: "50%", maxHeight: "50%", html: html, close: 'X' });
	currentHash = hash;
}

function postRatings() {
	$.post('prate', $('#ratings_form').serialize(), function(data) {
		$.colorbox.remove();
		$('#edited-' + currentHash).show();
	}, 'json');
}