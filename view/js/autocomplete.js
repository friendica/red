/**
 * Red people autocomplete
 *
 * require jQuery, jquery.textcomplete
 */
function mysearch(term, callback, backend_url, extra_channels) {
	var postdata = {
		start:0,
		count:100,
		search:term,
		type:'c',
	}

	if(extra_channels)
		postdata['extra_channels[]'] = extra_channels;
	
	$.ajax({
		type:'POST',
		url: backend_url,
		data: postdata,
		dataType: 'json',
		success:function(data){
			callback(data.items);
		},
	}).fail(function () {callback([]); }); // Callback must be invoked even if something went wrong.
}

function format(item) {
	return "<div class='{0}'><img src='{1}'>{2} ({3})</div>".format(item.taggable, item.photo, item.name, ((item.label) ? item.nick + ' ' + item.label : item.nick) )
}

function replace(item) {
	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;
	 // 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if(id.length > 16) 
		id = item.id.substring(0,16);
	return '$1$2'+item.nick.replace(' ','') + '+' + id;
}

/**
 * jQuery plugin 'contact_autocomplete'
 */
(function( $ ){
	$.fn.contact_autocomplete = function(backend_url, extra_channels = null) {

	// Autocomplete contacts
	contacts = {
		match: /(^|\s)(@\!*)([^ \n]+)$/,
		index: 3,
		search: function(term, callback) { mysearch(term, callback, backend_url, extra_channels); },
		replace: replace,
		template: format,
	}

	smilies = {
		match: /(^|\s)(:[a-z]{2,})$/,
		index: 2,
		search: function(term, callback) { $.getJSON('/smilies/json').done(function(data) { callback($.map(data, function(entry) { return entry['text'].indexOf(term) === 0 ? entry : null })) }) },
		template: function(item) { return item['icon'] + item['text'] },
		replace: function(item) { return "$1"+item['text'] + ' '; },
	}
	this.textcomplete([contacts,smilies],{className:'acpopup'});
  };
})( jQuery );
