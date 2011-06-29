/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

/* Macgirvin Aug-2010 changed from punbb to dfrn dialect */

(function() {
	tinymce.create('tinymce.plugins.BBCodePlugin', {
		init : function(ed, url) {
			var t = this, dialect = ed.getParam('bbcode_dialect', 'dfrn').toLowerCase();

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t['_' + dialect + '_bbcode2html'](o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = t['_' + dialect + '_bbcode2html'](o.content);

				if (o.get)
					o.content = t['_' + dialect + '_html2bbcode'](o.content);
			});
		},

		getInfo : function() {
			return {
				longname : 'BBCode Plugin',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/bbcode',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Private methods

		// HTML -> BBCode in DFRN dialect
		_dfrn_html2bbcode : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};




			/* oembed */
			function _h2b_cb(match) {
				text = bin2hex(match);
				function s_h2b(data) {
						match = data;
				}
				$.ajax({
					url: 'oembed/h2b?text=' + text,
					async: false,
					success: s_h2b,
					dataType: 'html'
				});
				return match;
			}
			s = s.replace(/<span class=\"oembed(.*?)<\/span>/gi, _h2b_cb);
			/* /oembed */


			// example: <strong> to [b]
			rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]");
			rep(/<span style=\"font-size:(.*?);\">(.*?)<\/span>/gi,"[size=$1]$2[/size]");
			rep(/<span style=\"color:(.*?);\">(.*?)<\/span>/gi,"[color=$1]$2[/color]");
			rep(/<font>(.*?)<\/font>/gi,"$1");
			rep(/<img.*?width=\"(.*?)\".*?height=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$1x$2]$3[/img]");
			rep(/<img.*?height=\"(.*?)\".*?width=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$2x$1]$3[/img]");
			rep(/<img.*?src=\"(.*?)\".*?height=\"(.*?)\".*?width=\"(.*?)\".*?\/>/gi,"[img=$3x$2]$1[/img]");
			rep(/<img.*?src=\"(.*?)\".*?width=\"(.*?)\".*?height=\"(.*?)\".*?\/>/gi,"[img=$2x$3]$1[/img]");
			rep(/<img.*?src=\"(.*?)\".*?\/>/gi,"[img]$1[/img]");
			rep(/<code>(.*?)<\/code>/gi,"[code]$1[/code]");
			rep(/<\/(strong|b)>/gi,"[/b]");
			rep(/<(strong|b)>/gi,"[b]");
			rep(/<\/(em|i)>/gi,"[/i]");
			rep(/<(em|i)>/gi,"[i]");
			rep(/<\/u>/gi,"[/u]");
			rep(/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/gi,"[u]$1[/u]");
			rep(/<u>/gi,"[u]");
			rep(/<blockquote[^>]*>/gi,"[quote]");
			rep(/<\/blockquote>/gi,"[/quote]");
			rep(/<br \/>/gi,"\n\n");
			rep(/<br\/>/gi,"\n\n");
			rep(/<br>/gi,"\n");
			rep(/<p>/gi,"");
			rep(/<\/p>/gi,"\n");
			rep(/&nbsp;/gi," ");
			rep(/&quot;/gi,"\"");
			rep(/&lt;/gi,"<");
			rep(/&gt;/gi,">");
			rep(/&amp;/gi,"&");

			return s; 
		},

		// BBCode -> HTML from DFRN dialect
		_dfrn_bbcode2html : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			// example: [b] to <strong>
			rep(/\n/gi,"<br />");
			rep(/\[b\]/gi,"<strong>");
			rep(/\[\/b\]/gi,"</strong>");
			rep(/\[i\]/gi,"<em>");
			rep(/\[\/i\]/gi,"</em>");
			rep(/\[u\]/gi,"<u>");
			rep(/\[\/u\]/gi,"</u>");
			rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
			rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
			rep(/\[img=(.*?)x(.*?)\](.*?)\[\/img\]/gi,"<img width=\"$1\" height=\"$2\" src=\"$3\" />");
			rep(/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />");
			rep(/\[color=(.*?)\](.*?)\[\/color\]/gi,"<span style=\"color: $1;\">$2</span>");
			rep(/\[size=(.*?)\](.*?)\[\/size\]/gi,"<span style=\"font-size: $1;\">$2</span>");
			rep(/\[code\](.*?)\[\/code\]/gi,"<code>$1</code>");
			rep(/\[quote.*?\](.*?)\[\/quote\]/gi,"<blockquote>$1</blockquote>");

			/* oembed */
			function _b2h_cb(match, url) {
				url = bin2hex(url);
				function s_b2h(data) {
						match = data;
				}
				$.ajax({
					url: 'oembed/b2h?url=' + url,
					async: false,
					success: s_b2h,
					dataType: 'html'
				});
				return match;
			}
			s = s.replace(/\[embed\](.*?)\[\/embed\]/gi, _b2h_cb);
			
			/* /oembed */

			return s; 
		}
	});

	// Register plugin
	tinymce.PluginManager.add('bbcode', tinymce.plugins.BBCodePlugin);
})();