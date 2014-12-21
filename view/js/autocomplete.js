/**
 * Red people autocomplete
 *
 * require jQuery, jquery.textcomplete
 */
function mysearch(term, callback, backend_url) {
	var postdata = {
		start:0,
		count:100,
		//search:term.substring(1),
		search:term,
		type:'c',
	}
	
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
	return "<img src='{0}' height='16px' width='16px'>{1} ({2})".format(item.photo, item.name, ((item.label) ? item.nick + ' ' + item.label : item.nick) )
}

function replace(item) {
	// $2 ensures that prefix (@,@!) is preserved
	return '$1$2'+item.nick.replace(' ','') + '+' + item.id;
}

/**
 * jQuery plugin 'contact_autocomplete'
 */
(function( $ ){
	$.fn.contact_autocomplete = function(backend_url) {

	// Autocomplete contacts
	contacts = {
		match: /(^|\s)(@!?)(\w{2,})$/,
		index: 3,
		search: function(term, callback) { mysearch(term, callback, backend_url); },
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
	this.textcomplete([contacts,smilies],{});
  };
})( jQuery );
