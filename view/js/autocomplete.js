/**
 * Red people autocomplete
 *
 * require jQuery, jquery.textcomplete
 */
function contact_search(term, callback, backend_url, type, extra_channels, spinelement) {
	if(spinelement) {
		$(spinelement).spin('tiny');
	}
	// Check if there is a cached result that contains the same information we would get with a full server-side search
	var bt = backend_url+type;
	if(!(bt in contact_search.cache)) contact_search.cache[bt] = {};

	var lterm = term.toLowerCase(); // Ignore case
	for(var t in contact_search.cache[bt]) {
		if(lterm.indexOf(t) >= 0) { // A more broad search has been performed already, so use those results
			$(spinelement).spin(false);
			// Filter old results locally
			var matching = contact_search.cache[bt][t].filter(function (x) { return (x.name.toLowerCase().indexOf(lterm) >= 0 || (typeof x.nick !== 'undefined' && x.nick.toLowerCase().indexOf(lterm) >= 0)); }); // Need to check that nick exists because groups don't have one
			matching.unshift({taggable:false, text: term, replace: term});
			setTimeout(function() { callback(matching); } , 1); // Use "pseudo-thread" to avoid some problems
			return;
		}
	}

	var postdata = {
		start:0,
		count:100,
		search:term,
		type:type,
	};

	if(typeof extra_channels !== 'undefined' && extra_channels)
		postdata['extra_channels[]'] = extra_channels;

	$.ajax({
		type:'POST',
		url: backend_url,
		data: postdata,
		dataType: 'json',
		success: function(data){
			// Cache results if we got them all (more information would not improve results)
			// data.count represents the maximum number of items
			if(data.items.length -1 < data.count) {
				contact_search.cache[bt][lterm] = data.items;
			}
			var items = data.items.slice(0);
			items.unshift({taggable:false, text: term, replace: term});
			callback(items);
			$(spinelement).spin(false);
		},
	}).fail(function () {callback([]); }); // Callback must be invoked even if something went wrong.
}
contact_search.cache = {};


function contact_format(item) {
	// Show contact information if not explicitly told to show something else
	if(typeof item.text === 'undefined') {
		var desc = ((item.label) ? item.nick + ' ' + item.label : item.nick);
		if(typeof desc === 'undefined') desc = '';
		if(desc) desc = ' ('+desc+')';
		return "<div class='{0}' title='{4}'><img src='{1}'><span class='contactname'>{2}</span><span class='dropdown-sub-text'>{3}</span><div class='clear'></div></div>".format(item.taggable, item.photo, item.name, desc, item.link);
	}
	else
		return "<div>" + item.text + "</div>";
}

function editor_replace(item) {
	if(typeof item.replace !== 'undefined') {
		return '$1$2' + item.replace;
	}

	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;
	 // 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if(id.length > 16) 
		id = item.id.substring(0,16);

	return '$1$2' + item.nick.replace(' ', '') + '+' + id + ' ';
}

function basic_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.name+' ';
}

function submit_form(e) {
	$(e).parents('form').submit();
}

/**
 * jQuery plugin 'editor_autocomplete'
 */
(function( $ ) {
	$.fn.editor_autocomplete = function(backend_url, extra_channels) {
		if (typeof extra_channels === 'undefined') extra_channels = false;

		// Autocomplete contacts
		contacts = {
			match: /(^|\s)(@\!*)([^ \n]+)$/,
			index: 3,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'c', extra_channels, spinelement=false); },
			replace: editor_replace,
			template: contact_format,
		};

		smilies = {
			match: /(^|\s)(:[a-z]{2,})$/,
			index: 2,
			search: function(term, callback) { $.getJSON('/smilies/json').done(function(data) { callback($.map(data, function(entry) { return entry.text.indexOf(term) === 0 ? entry : null; })); }); },
			template: function(item) { return item.icon + item.text; },
			replace: function(item) { return "$1" + item.text + ' '; },
		};
		this.attr('autocomplete','off');
		this.textcomplete([contacts,smilies], {className:'acpopup', zIndex:1020});
	};
})( jQuery );

/**
 * jQuery plugin 'search_autocomplete'
 */
(function( $ ) {
	$.fn.search_autocomplete = function(backend_url) {
		// Autocomplete contacts
		contacts = {
			match: /(^@)([^\n]{2,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', [], spinelement='#nav-search-spinner'); },
			replace: basic_replace,
			template: contact_format,
		};
		this.attr('autocomplete', 'off');
		var a = this.textcomplete([contacts], {className:'acpopup', maxCount:100, zIndex: 1020, appendTo:'nav'});
		a.on('textComplete:select', function(e, value, strategy) { submit_form(this); });
	};
})( jQuery );

(function( $ ) {
	$.fn.contact_autocomplete = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		contacts = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ,[], spinelement=false); },
			replace: basic_replace,
			template: contact_format,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([contacts], {className:'acpopup', zIndex:1020});

		if(autosubmit)
			a.on('textComplete:select', function(e,value,strategy) { submit_form(this); });

		if(typeof onselect !== 'undefined')
			a.on('textComplete:select', function(e, value, strategy) { onselect(value); });
	};
})( jQuery );