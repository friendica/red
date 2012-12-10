/*
WYSIWYG-BBCODE editor
Copyright (c) 2009, Jitbit Sotware, http://www.jitbit.com/
PROJECT HOME: http://wysiwygbbcode.codeplex.com/
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY Jitbit Software ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL Jitbit Software BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

var myeditor, ifm;
var body_id, textboxelement;
var content;
var isIE = /msie|MSIE/.test(navigator.userAgent);
var isChrome = /Chrome/.test(navigator.userAgent);
var isSafari = /Safari/.test(navigator.userAgent) && !isChrome;
var browser = isIE || window.opera;
var textRange;
var enter = 0;
var editorVisible = false;
var enableWysiwyg = false;

function rep(re, str) {
	content = content.replace(re, str);
}

function initEditor(textarea_id, wysiwyg) {
	if(wysiwyg!=undefined)
		enableWysiwyg = wysiwyg;
	else
		enableWysiwyg = true;
    body_id = textarea_id;
    textboxelement = document.getElementById(body_id);
    textboxelement.setAttribute('class', 'editorBBCODE');
    textboxelement.className = "editorBBCODE";
    if (enableWysiwyg) {
        ifm = document.createElement("iframe");
        ifm.setAttribute("id", "rte");
        ifm.setAttribute("frameborder", "0");
        ifm.style.width = textboxelement.style.width;
        ifm.style.height = textboxelement.style.height;
        textboxelement.parentNode.insertBefore(ifm, textboxelement);
        textboxelement.style.display = 'none';
        if (ifm) {
            ShowEditor();
        } else
            setTimeout('ShowEditor()', 100);
    }
}

function getStyle(el,styleProp)
{
	var x = document.getElementById(el);
	if (x.currentStyle)
		var y = x.currentStyle[styleProp];
	else if (window.getComputedStyle)
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	return y;
}

function ShowEditor() {
    if (!enableWysiwyg) return;
    editorVisible = true;
    content = document.getElementById(body_id).value;
    myeditor = ifm.contentWindow.document;
    bbcode2html();
    myeditor.designMode = "on";
    myeditor.open();
    myeditor.write('<html><head><link href="editor.css" rel="Stylesheet" type="text/css" /></head>');
    myeditor.write('<body style="margin:0px 0px 0px 0px" class="editorWYSIWYG">');
    myeditor.write(content);
    myeditor.write('</body></html>');
    myeditor.close();
    if (myeditor.attachEvent) {
        if(parent.ProcessKeyPress)
            myeditor.attachEvent("onkeydown", parent.ProcessKeyPress);
		myeditor.attachEvent("onkeypress", kp);
    }
    else if (myeditor.addEventListener) {
        if (parent.ProcessKeyPress)
            myeditor.addEventListener("keydown", parent.ProcessKeyPress, true);
		myeditor.addEventListener("keypress",kp,true);
    }
}

function SwitchEditor() {
    if (editorVisible) {
        doCheck();
        ifm.style.display = 'none';
        textboxelement.style.display = '';
		editorVisible = false;
    }
    else {
        if (enableWysiwyg && ifm) {
            ifm.style.display = '';
            textboxelement.style.display = 'none';
            ShowEditor();
            editorVisible = true;
        }
    }
}

function html2bbcode() {
	rep(/<img\s[^<>]*?src=\"?([^<>]*?)\"?(\s[^<>]*)?\/?>/gi,"[img]$1[/img]");
	rep(/<\/(strong|b)>/gi, "[/b]");
	rep(/<(strong|b)(\s[^<>]*)?>/gi,"[b]");
	rep(/<\/(em|i)>/gi,"[/i]");
	rep(/<(em|i)(\s[^<>]*)?>/gi,"[i]");
	rep(/<\/u>/gi, "[/u]");
	rep(/\n/gi, " ");
	rep(/\r/gi, " ");
	rep(/<u(\s[^<>]*)?>/gi, "[u]");
	rep(/<div><br(\s[^<>]*)?>/gi, "<div>");//chrome-safari fix to prevent double linefeeds
	rep(/<br(\s[^<>]*)?>/gi,"\n");
	rep(/<p(\s[^<>]*)?>/gi,"");
	rep(/<\/p>/gi, "\n");
	rep(/<ul>/gi, "[ul]");
	rep(/<\/ul>/gi, "[/ul]");
	rep(/<ol>/gi, "[ol]");
	rep(/<\/ol>/gi, "[/ol]");
	rep(/<li>/gi, "[li]");
	rep(/<\/li>/gi, "[/li]");
	rep(/<\/div>\s*<div([^<>]*)>/gi, "</span>\n<span$1>");//chrome-safari fix to prevent double linefeeds
	rep(/<div([^<>]*)>/gi,"\n<span$1>");
	rep(/<\/div>/gi,"</span>\n");
	rep(/&nbsp;/gi," ");
	rep(/&quot;/gi,"\"");
	rep(/&amp;/gi,"&");
	var sc, sc2;
	do {
		sc = content;
		rep(/<font\s[^<>]*?color=\"?([^<>]*?)\"?(\s[^<>]*)?>([^<>]*?)<\/font>/gi,"[color=$1]$3[/color]");
		if(sc==content)
			rep(/<font[^<>]*>([^<>]*?)<\/font>/gi,"$1");
		rep(/<a\s[^<>]*?href=\"?([^<>]*?)\"?(\s[^<>]*)?>([^<>]*?)<\/a>/gi,"[url=$1]$3[/url]");
		sc2 = content;
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?font-weight: ?bold;?\"?\s*([^<]*?)<\/\1>/gi,"[b]<$1 style=$2</$1>[/b]");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?font-weight: ?normal;?\"?\s*([^<]*?)<\/\1>/gi,"<$1 style=$2</$1>");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?font-style: ?italic;?\"?\s*([^<]*?)<\/\1>/gi,"[i]<$1 style=$2</$1>[/i]");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?font-style: ?normal;?\"?\s*([^<]*?)<\/\1>/gi,"<$1 style=$2</$1>");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?text-decoration: ?underline;?\"?\s*([^<]*?)<\/\1>/gi,"[u]<$1 style=$2</$1>[/u]");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?text-decoration: ?none;?\"?\s*([^<]*?)<\/\1>/gi,"<$1 style=$2</$1>");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?color: ?([^<>]*?);\"?\s*([^<]*?)<\/\1>/gi, "[color=$2]<$1 style=$3</$1>[/color]");
		rep(/<(span|blockquote|pre)\s[^<>]*?style=\"?font-family: ?([^<>]*?);\"?\s*([^<]*?)<\/\1>/gi, "[font=$2]<$1 style=$3</$1>[/font]");
		rep(/<(blockquote|pre)\s[^<>]*?style=\"?\"? (class=|id=)([^<>]*)>([^<>]*?)<\/\1>/gi, "<$1 $2$3>$4</$1>");
		rep(/<pre>([^<>]*?)<\/pre>/gi, "[code]$1[/code]");
		rep(/<span\s[^<>]*?style=\"?\"?>([^<>]*?)<\/span>/gi, "$1");
		if(sc2==content) {
			rep(/<span[^<>]*>([^<>]*?)<\/span>/gi, "$1");
			sc2 = content;
		}
	}while(sc!=content)
	rep(/<[^<>]*>/gi,"");
	rep(/&lt;/gi,"<");
	rep(/&gt;/gi,">");
	
	do {
		sc = content;
		rep(/\[(b|i|u)\]\[quote([^\]]*)\]([\s\S]*?)\[\/quote\]\[\/\1\]/gi, "[quote$2][$1]$3[/$1][/quote]");
		rep(/\[color=([^\]]*)\]\[quote([^\]]*)\]([\s\S]*?)\[\/quote\]\[\/color\]/gi, "[quote$2][color=$1]$3[/color][/quote]");
		rep(/\[(b|i|u)\]\[code\]([\s\S]*?)\[\/code\]\[\/\1\]/gi, "[code][$1]$2[/$1][/code]");
		rep(/\[color=([^\]]*)\]\[code\]([\s\S]*?)\[\/code\]\[\/color\]/gi, "[code][color=$1]$2[/color][/code]");
	}while(sc!=content)

	//clean up empty tags
	do {
		sc = content;
		rep(/\[b\]\[\/b\]/gi, "");
		rep(/\[i\]\[\/i\]/gi, "");
		rep(/\[u\]\[\/u\]/gi, "");
		rep(/\[quote[^\]]*\]\[\/quote\]/gi, "");
		rep(/\[code\]\[\/code\]/gi, "");
		rep(/\[url=([^\]]+)\]\[\/url\]/gi, "");
		rep(/\[img\]\[\/img\]/gi, "");
		rep(/\[color=([^\]]*)\]\[\/color\]/gi, "");
	}while(sc!=content)
}

function bbcode2html() {
	// example: [b] to <strong>
	rep(/\</gi,"&lt;"); //removing html tags
	rep(/\>/gi,"&gt;");
	
	rep(/\n/gi, "<br />");
	rep(/\[ul\]/gi, "<ul>");
	rep(/\[\/ul\]/gi, "</ul>");
	rep(/\[ol\]/gi, "<ol>");
	rep(/\[\/ol\]/gi, "</ol>");
	rep(/\[li\]/gi, "<li>");
	rep(/\[\/li\]/gi, "</li>");
	if(browser) {
		rep(/\[b\]/gi,"<strong>");
		rep(/\[\/b\]/gi,"</strong>");
		rep(/\[i\]/gi,"<em>");
		rep(/\[\/i\]/gi,"</em>");
		rep(/\[u\]/gi,"<u>");
		rep(/\[\/u\]/gi,"</u>");
	}else {
		rep(/\[b\]/gi,"<span style=\"font-weight: bold;\">");
		rep(/\[i\]/gi,"<span style=\"font-style: italic;\">");
		rep(/\[u\]/gi,"<span style=\"text-decoration: underline;\">");
		rep(/\[\/(b|i|u)\]/gi,"</span>");
	}
	rep(/\[img\]([^\"]*?)\[\/img\]/gi,"<img src=\"$1\" />");
	var sc;
	do {
		sc = content;
		rep(/\[url=([^\]]+)\]([\s\S]*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
		rep(/\[url\]([\s\S]*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
		if(browser) {
		    rep(/\[color=([^\]]*?)\]([\s\S]*?)\[\/color\]/gi, "<font color=\"$1\">$2</font>");
		    rep(/\[font=([^\]]*?)\]([\s\S]*?)\[\/font\]/gi, "<font face=\"$1\">$2</font>");
		} else {
		    rep(/\[color=([^\]]*?)\]([\s\S]*?)\[\/color\]/gi, "<span style=\"color: $1;\">$2</span>");
		    rep(/\[font=([^\]]*?)\]([\s\S]*?)\[\/font\]/gi, "<span style=\"font-family: $1;\">$2</span>");
		}
		rep(/\[code\]([\s\S]*?)\[\/code\]/gi,"<pre>$1</pre>&nbsp;");
	}while(sc!=content);
}

function doCheck() {
	if (!editorVisible) {
        ShowEditor();
    }
	content = myeditor.body.innerHTML;
	html2bbcode();
	document.getElementById(body_id).value = content;
}

function stopEvent(evt){
	evt || window.event;
	if (evt.stopPropagation){
		evt.stopPropagation();
		evt.preventDefault();
	}else if(typeof evt.cancelBubble != "undefined"){
		evt.cancelBubble = true;
		evt.returnValue = false;
	}
	return false;
}

function doQuote() {
    if (editorVisible) {
        ifm.contentWindow.focus();
        if (isIE) {
            textRange = ifm.contentWindow.document.selection.createRange();
            var newTxt = "[quote=]" + textRange.text + "[/quote]";
            textRange.text = newTxt;
        }
        else {
            var edittext = ifm.contentWindow.getSelection().getRangeAt(0);
            var original = edittext.toString();
            edittext.deleteContents();
            edittext.insertNode(document.createTextNode("[quote=]" + original + "[/quote]"));
        }
    }
    else {
        AddTag('[quote=]', '[/quote]');
    }
}

function kp(e){
	if(isIE)
		var k = e.keyCode;
	else
		var k = e.which;
	if(k==13) {
		if(isIE) {
		    var r = myeditor.selection.createRange();
		    if (r.parentElement().tagName.toLowerCase() != "li") {
		        r.pasteHTML('<br/>');
		        if (r.move('character'))
		            r.move('character', -1);
		        r.select();
		        stopEvent(e);
		        return false;
		    }
		}
	}else
		enter = 0;
}

function InsertSmile(txt) {
    InsertText(txt);
    document.getElementById('divSmilies').style.display = 'none';
}
function InsertYoutube() {
    InsertText("http://www.youtube.com/watch?v=XXXXXXXXXXX");
}
function InsertText(txt) {
    if (editorVisible)
        insertHtml(txt);
    else
        textboxelement.value += txt;
}

function doClick(command) {
    if (editorVisible) {
        ifm.contentWindow.focus();
        myeditor.execCommand(command, false, null);
    }
    else {
        switch (command) {
            case 'bold':
                AddTag('[b]', '[/b]'); break;
            case 'italic':
                AddTag('[i]', '[/i]'); break;
            case 'underline':
                AddTag('[u]', '[/u]'); break;
            case 'InsertUnorderedList':
                AddTag('[ul][li]', '[/li][/ul]'); break;
        }
    }
}

function doColor(color) {
  ifm.contentWindow.focus();
  if (isIE) {
      textRange = ifm.contentWindow.document.selection.createRange();
      textRange.select();
  }
  myeditor.execCommand('forecolor', false, color);
}

function doLink() {
    if (editorVisible) {
        ifm.contentWindow.focus();
        var mylink = prompt("Enter a URL:", "http://");
        if ((mylink != null) && (mylink != "")) {
            if (isIE) { //IE
                var range = ifm.contentWindow.document.selection.createRange();
                if (range.text == '') {
                    range.pasteHTML("<a href='" + mylink + "'>" + mylink + "</a>");
                }
                else
                    myeditor.execCommand("CreateLink", false, mylink);
            }
            else if (window.getSelection) { //FF
                var userSelection = ifm.contentWindow.getSelection().getRangeAt(0);
                if(userSelection.toString().length==0)
                    myeditor.execCommand('inserthtml', false, "<a href='" + mylink + "'>" + mylink + "</a>");
                else
                    myeditor.execCommand("CreateLink", false, mylink);
            }
            else
                myeditor.execCommand("CreateLink", false, mylink);
        }
    }
    else {
        AddTag('[url=',']click here[/url]');
    }
}
function doImage() {
    if (editorVisible) {
        ifm.contentWindow.focus();
        myimg = prompt('Enter Image URL:', 'http://');
        if ((myimg != null) && (myimg != "")) {
            myeditor.execCommand('InsertImage', false, myimg);
        }
    }
    else {
        AddTag('[img]', '[/img]');
    }
}

function insertHtml(html) {
    ifm.contentWindow.focus();
    if (isIE)
        ifm.contentWindow.document.selection.createRange().pasteHTML(html);
    else
        myeditor.execCommand('inserthtml', false, html);
}

//textarea-mode functions
function MozillaInsertText(element, text, pos) {
    element.value = element.value.slice(0, pos) + text + element.value.slice(pos);
}

function AddTag(t1, t2) {
    var element = textboxelement;
    if (isIE) {
        if (document.selection) {
            element.focus();

            var txt = element.value;
            var str = document.selection.createRange();

            if (str.text == "") {
                str.text = t1 + t2;
            }
            else if (txt.indexOf(str.text) >= 0) {
                str.text = t1 + str.text + t2;
            }
            else {
                element.value = txt + t1 + t2;
            }
            str.select();
        }
    }
    else if (typeof(element.selectionStart) != 'undefined') {
        var sel_start = element.selectionStart;
        var sel_end = element.selectionEnd;
        MozillaInsertText(element, t1, sel_start);
        MozillaInsertText(element, t2, sel_end + t1.length);
        element.selectionStart = sel_start;
        element.selectionEnd = sel_end + t1.length + t2.length;
        element.focus();
    }
    else {
        element.value = element.value + t1 + t2;
    }
}

//=======color picker
function getScrollY() { var scrOfX = 0, scrOfY = 0; if (typeof (window.pageYOffset) == 'number') { scrOfY = window.pageYOffset; scrOfX = window.pageXOffset; } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) { scrOfY = document.body.scrollTop; scrOfX = document.body.scrollLeft; } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) { scrOfY = document.documentElement.scrollTop; scrOfX = document.documentElement.scrollLeft; } return scrOfY; }

document.write("<style type='text/css'>.colorpicker201{visibility:hidden;display:none;position:absolute;background:#FFF;z-index:999;filter:progid:DXImageTransform.Microsoft.Shadow(color=#D0D0D0,direction=135);}.o5582brd{padding:0;width:12px;height:14px;border-bottom:solid 1px #DFDFDF;border-right:solid 1px #DFDFDF;}a.o5582n66,.o5582n66,.o5582n66a{font-family:arial,tahoma,sans-serif;text-decoration:underline;font-size:9px;color:#666;border:none;}.o5582n66,.o5582n66a{text-align:center;text-decoration:none;}a:hover.o5582n66{text-decoration:none;color:#FFA500;cursor:pointer;}.a01p3{padding:1px 4px 1px 2px;background:whitesmoke;border:solid 1px #DFDFDF;}</style>");

function getTop2() { csBrHt = 0; if (typeof (window.innerWidth) == 'number') { csBrHt = window.innerHeight; } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) { csBrHt = document.documentElement.clientHeight; } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) { csBrHt = document.body.clientHeight; } ctop = ((csBrHt / 2) - 115) + getScrollY(); return ctop; }
var nocol1 = "&#78;&#79;&#32;&#67;&#79;&#76;&#79;&#82;",
clos1 = "X";

function getLeft2() { var csBrWt = 0; if (typeof (window.innerWidth) == 'number') { csBrWt = window.innerWidth; } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) { csBrWt = document.documentElement.clientWidth; } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) { csBrWt = document.body.clientWidth; } cleft = (csBrWt / 2) - 125; return cleft; }

//function setCCbldID2(val, textBoxID) { document.getElementById(textBoxID).value = val; }
function setCCbldID2(val) { if (editorVisible) doColor(val); else AddTag('[color=' + val + ']', '[/color]'); }

function setCCbldSty2(objID, prop, val) {
    switch (prop) {
        case "bc": if (objID != 'none') { document.getElementById(objID).style.backgroundColor = val; }; break;
        case "vs": document.getElementById(objID).style.visibility = val; break;
        case "ds": document.getElementById(objID).style.display = val; break;
        case "tp": document.getElementById(objID).style.top = val; break;
        case "lf": document.getElementById(objID).style.left = val; break;
    }
}

function putOBJxColor2(Samp, pigMent, textBoxId) { if (pigMent != 'x') { setCCbldID2(pigMent, textBoxId); setCCbldSty2(Samp, 'bc', pigMent); } setCCbldSty2('colorpicker201', 'vs', 'hidden'); setCCbldSty2('colorpicker201', 'ds', 'none'); }

function showColorGrid2(Sam, textBoxId) {
    var objX = new Array('00', '33', '66', '99', 'CC', 'FF');
    var c = 0;
    var xl = '"' + Sam + '","x", "' + textBoxId + '"'; var mid = '';
    mid += '<table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" style="border:solid 0px #F0F0F0;padding:2px;"><tr>';
    mid += "<td colspan='9' align='left' style='margin:0;padding:2px;height:12px;' ><input class='o5582n66' type='text' size='12' id='o5582n66' value='#FFFFFF'><input class='o5582n66a' type='text' size='2' style='width:14px;' id='o5582n66a' onclick='javascript:alert(\"click on selected swatch below...\");' value='' style='border:solid 1px #666;'></td><td colspan='9' align='right'><a class='o5582n66' href='javascript:onclick=putOBJxColor2(" + xl + ")'><span class='a01p3'>" + clos1 + "</span></a></td></tr><tr>";
    var br = 1;
    for (o = 0; o < 6; o++) {
        mid += '</tr><tr>';
        for (y = 0; y < 6; y++) {
            if (y == 3) { mid += '</tr><tr>'; }
            for (x = 0; x < 6; x++) {
                var grid = '';
                grid = objX[o] + objX[y] + objX[x];
                var b = "'" + Sam + "','" + grid + "', '" + textBoxId + "'";
                mid += '<td class="o5582brd" style="background-color:#' + grid + '"><a class="o5582n66"  href="javascript:onclick=putOBJxColor2(' + b + ');" onmouseover=javascript:document.getElementById("o5582n66").value="#' + grid + '";javascript:document.getElementById("o5582n66a").style.backgroundColor="#' + grid + '";  title="#' + grid + '"><div style="width:12px;height:14px;"></div></a></td>';
                c++;
            }
        }
    }
    mid += "</tr></table>";
    //var ttop=getTop2();
    //setCCbldSty2('colorpicker201','tp',ttop);
    //document.getElementById('colorpicker201').style.left=getLeft2();
    document.getElementById('colorpicker201').innerHTML = mid;
    setCCbldSty2('colorpicker201', 'vs', 'visible');
    setCCbldSty2('colorpicker201', 'ds', 'inline');
}