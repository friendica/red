
function resize_iframe()
{
	if(typeof(window.innerHeight) != 'undefined') {
		var height=window.innerHeight;//Firefox
	}
	else {
		if (typeof(document.body.clientHeight) != 'undefined')
		{
			var height=document.body.clientHeight;//IE
		}
	}

	//resize the iframe according to the size of the
	//window (all these should be on the same line)
	document.getElementById("remote-channel").style.height=parseInt(height-document.getElementById("remote-channel").offsetTop-8)+"px";
}

window.onresize=resize_iframe; 
