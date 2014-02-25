
function str_rot13 (str) {
  // http://kevin.vanzonneveld.net
  // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // +   improved by: Ates Goral (http://magnetiq.com)
  // +   bugfixed by: Onno Marsman
  // +   improved by: Rafa? Kukawski (http://blog.kukawski.pl)
  // *     example 1: str_rot13('Kevin van Zonneveld');
  // *     returns 1: 'Xriva ina Mbaariryq'
  // *     example 2: str_rot13('Xriva ina Mbaariryq');
  // *     returns 2: 'Kevin van Zonneveld'
  // *     example 3: str_rot13(33);
  // *     returns 3: '33'
	return (str + '').replace(/[a-z]/gi, function (s) {
		return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13));
	});
}


// Arrays for pluggable encryptors/decryptors

var red_encryptors = new Array();
var red_decryptors = new Array();

// We probably just want the element where the text is and find it ourself. e.g. if 
// there is highlighted text use it, otherwise use the entire text.
// So the third element may be useless. Fix also in view/tpl/jot.tpl before 
// adding to all the editor templates and enabling the feature

// Should probably do some input sanitising and dealing with bbcode, hiding key text, and displaying
// results in a lightbox and/or popup form are left as an exercise for the reader. 


function red_encrypt(alg, elem,text) {
	var enc_text = '';
	var newdiv = '';

	if(typeof tinyMCE !== "undefined")
		tinyMCE.triggerSave(false,true);

	var text = $(elem).val();

	// key and hint need to be localised

        var passphrase = prompt(aStr['passphrase']);
        // let the user cancel this dialogue
        if (passphrase == null)
                return false;
        var enc_key = bin2hex(passphrase);

	// If you don't provide a key you get rot13, which doesn't need a key
	// but consequently isn't secure.  

	if(! enc_key)
		alg = 'rot13';

	if((alg == 'rot13') || (alg == 'triple-rot13'))
		newdiv = "[crypt alg='rot13']" + str_rot13(text) + '[/crypt]';

	if(alg == 'aes256') {

			// This is the prompt we're going to use when the receiver tries to open it.
			// Maybe "Grandma's maiden name" or "our secret place" or something. 

			var enc_hint = bin2hex(prompt(aStr['passhint']));

			enc_text = CryptoJS.AES.encrypt(text,enc_key);

			encrypted = enc_text.toString();

			newdiv = "[crypt alg='aes256' hint='" + enc_hint + "']" + encrypted + '[/crypt]';
	}
	if(alg == 'rabbit') {

			// This is the prompt we're going to use when the receiver tries to open it.
			// Maybe "Grandma's maiden name" or "our secret place" or something. 

			var enc_hint = bin2hex(prompt(aStr['passhint']));

			enc_text = CryptoJS.Rabbit.encrypt(text,enc_key);
			encrypted = enc_text.toString();

			newdiv = "[crypt alg='rabbit' hint='" + enc_hint + "']" + encrypted + '[/crypt]';
	}
	if(alg == '3des') {

			// This is the prompt we're going to use when the receiver tries to open it.
			// Maybe "Grandma's maiden name" or "our secret place" or something. 

			var enc_hint = bin2hex(prompt(aStr['passhint']));

			enc_text = CryptoJS.TripleDES.encrypt(text,enc_key);
			encrypted = enc_text.toString();

			newdiv = "[crypt alg='3des' hint='" + enc_hint + "']" + encrypted + '[/crypt]';
	}
	if((red_encryptors.length) && (! newdiv.length)) {
		for(var i = 0; i < red_encryptors.length; i ++) {
			newdiv = red_encryptors[i](alg,text);
			if(newdiv.length)
				break;
		}
	}

	enc_key = '';

//	alert(newdiv);

	// This might be a comment box on a page with a tinymce editor
	// so check if there is a tinymce editor but also check the display
	// property of our source element - because a tinymce instance
	// will have display "none". If a normal textarea such as in a comment
	// box has display "none" you wouldn't be able to type in it.
	
	if($(elem).css('display') == 'none' && typeof tinyMCE !== "undefined") {
		tinyMCE.activeEditor.setContent(newdiv);
	}
	else {
		$(elem).val(newdiv);
	}

//	textarea = document.getElementById(elem);
//	if (document.selection) {
//		textarea.focus();
//		selected = document.selection.createRange();
//		selected.text = newdiv;
//	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
//		var start = textarea.selectionStart;
//		var end = textarea.selectionEnd;
//		textarea.value = textarea.value.substring(0, start) + newdiv + textarea.value.substring(end, textarea.value.length);
//	}
}

function red_decrypt(alg,hint,text,elem) {

	var dec_text = '';

	if(alg == 'rot13' || alg == 'triple-rot13')
		dec_text = str_rot13(text);
	else {
		var enc_key = bin2hex(prompt((hint.length) ? hex2bin(hint) : aStr['passphrase']));
	}

	if(alg == 'aes256') {
		dec_text = CryptoJS.AES.decrypt(text,enc_key);
	}
	if(alg == 'rabbit') {
		dec_text = CryptoJS.Rabbit.decrypt(text,enc_key);
	}
	if(alg == '3des') {
		dec_text = CryptoJS.TripleDES.decrypt(text,enc_key);
	}

	if((red_decryptors.length) && (! dec_text.length)) {
		for(var i = 0; i < red_decryptors.length; i ++) {
			dec_text = red_decryptors[i](alg,text,enc_key);
			if(dec_text.length)
				break;
		}
	}

	enc_key = '';

	// Not sure whether to drop this back in the conversation display.
	// It probably needs a lightbox or popup window because any conversation 
	// updates could 
	// wipe out the text and make you re-enter the key if it was in the
	// conversation. For now we do that so you can read it.

	var dec_result = dec_text.toString(CryptoJS.enc.Utf8);
	delete dec_text;

	// incorrect decryptions *usually* but don't always have zero length
	// If the person typo'd let them try again without reloading the page
	// otherwise they'll have no "padlock" to click to try again.

	if(dec_result.length) {
		$(elem).html(b2h(dec_result));
		dec_result = '';
	}
}
	
	



function base64_encode (data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Tyler Akins (http://rumkin.com)
  // +   improved by: Bayron Guevara
  // +   improved by: Thunder.m
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Pellentesque Malesuada
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Rafa? Kukawski (http://kukawski.pl)
  // *     example 1: base64_encode('Kevin van Zonneveld');
  // *     returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
  // mozilla has this native
  // - but breaks in 2.0.0.12!
  //if (typeof this.window['btoa'] === 'function') {
  //    return btoa(data);
  //}
	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    enc = "",
    tmp_arr = [];

	if (!data) {
		return data;
	}

	do { // pack three octets into four hexets
		o1 = data.charCodeAt(i++);
		o2 = data.charCodeAt(i++);
		o3 = data.charCodeAt(i++);

		bits = o1 << 16 | o2 << 8 | o3;

		h1 = bits >> 18 & 0x3f;
		h2 = bits >> 12 & 0x3f;
		h3 = bits >> 6 & 0x3f;
		h4 = bits & 0x3f;

    // use hexets to index into b64, and append result to encoded string
		tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
	} while (i < data.length);

	enc = tmp_arr.join('');

	var r = data.length % 3;

	return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);

}


function base64_decode (data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Tyler Akins (http://rumkin.com)
  // +   improved by: Thunder.m
  // +      input by: Aman Gupta
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Pellentesque Malesuada
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
  // *     returns 1: 'Kevin van Zonneveld'
  // mozilla has this native
  // - but breaks in 2.0.0.12!
  //if (typeof this.window['atob'] === 'function') {
  //    return atob(data);
  //}
	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    dec = "",
    tmp_arr = [];

	if (!data) {
		return data;
	}

	data += '';

	do { // unpack four hexets into three octets using index points in b64
		h1 = b64.indexOf(data.charAt(i++));
		h2 = b64.indexOf(data.charAt(i++));
		h3 = b64.indexOf(data.charAt(i++));
		h4 = b64.indexOf(data.charAt(i++));

		bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

		o1 = bits >> 16 & 0xff;
		o2 = bits >> 8 & 0xff;
		o3 = bits & 0xff;

		if (h3 == 64) {
			tmp_arr[ac++] = String.fromCharCode(o1);
		} else if (h4 == 64) {
			tmp_arr[ac++] = String.fromCharCode(o1, o2);
		} else {
			tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
		}
	} while (i < data.length);

	dec = tmp_arr.join('');

	return dec;
}


