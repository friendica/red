/**
 * Red people autocomplete
 *
 * require jQuery, jquery.textcomplete
 */
function contact_search(term, callback, backend_url, type, extra_channels) {
	var postdata = {
		start:0,
		count:100,
		search:term,
		type:type,
	}

	if(typeof extra_channels !== 'undefined' && extra_channels)
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

function contact_format(item) {
	return "<div class='{0}' title='{4}'><img src='{1}'>{2} ({3})</div>".format(item.taggable, item.photo, item.name, ((item.label) ? item.nick + ' ' + item.label : item.nick), item.link )
}

function editor_replace(item) {
	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;
	 // 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if(id.length > 16) 
		id = item.id.substring(0,16);
	return '$1$2'+item.nick.replace(' ','') + '+' + id;
}

function basic_replace(item) {
	return '$1$2'+item.nick;
}

/**
 * jQuery plugin 'editor_autocomplete'
 */
(function( $ ){
	$.fn.editor_autocomplete = function(backend_url, extra_channels) {
	if (typeof extra_channels === 'undefined') extra_channels = false;

	// Autocomplete contacts
	contacts = {
		match: /(^|\s)(@\!*)([^ \n]+)$/,
		index: 3,
		search: function(term, callback) { contact_search(term, callback, backend_url, 'c', extra_channels); },
		replace: editor_replace,
		template: contact_format,
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

/**
 * jQuery plugin 'search_autocomplete'
 */
(function( $ ){
	$.fn.search_autocomplete = function(backend_url) {

	// Autocomplete contacts
	contacts = {
		match: /(^)(@)([^\n]+)$/,
		index: 3,
		search: function(term, callback) { contact_search(term, callback, backend_url, 'x',[]); },
		replace: basic_replace,
		template: contact_format,
	}
	this.textcomplete([contacts],{className:'acpopup'});
  };
})( jQuery );
