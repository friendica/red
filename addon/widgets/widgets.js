/**
 * @author Fabio Comuni
 */

var f9a_widget_$widget_id = {
	entrypoint : "$entrypoint",
	key	: "$key",
	widgetid: "$widget_id",
	argstr: "$args",
	xmlhttp : null,
	
	getXHRObj : function(){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
		 	this.xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			this.xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	},
	
	dorequest : function(args, cb) {
		if (args===null) args = new Array();
		args['k']=this.key;
		args['s']=window.location;
		args['a']=this.argstr;
		var urlencodedargs = new Array();
		for(k in args){ urlencodedargs.push( encodeURIComponent(k)+"="+encodeURIComponent(args[k]) ); }
	
		var url = this.entrypoint + "?"+ urlencodedargs.join("&");

		this.xmlhttp.open("GET", url  ,true);
		this.xmlhttp.send();
		this.xmlhttp.obj = this;
		this.xmlhttp.onreadystatechange=function(){
		  if (this.readyState==4){
		  	if (this.status==200) {
		    	cb(this.obj, this.responseText);
			} else {
		  		document.getElementById(this.obj.widgetid).innerHTML="Error loading widget.";
		  	}
		  }
		} 

	},
	
	requestcb: function(obj, responseText) {
		document.getElementById(obj.widgetid).innerHTML=responseText;
	},
	
	load : function (){
		this.getXHRObj();
		this.dorequest(null, this.requestcb);
	}

};

(function() {
	f9a_widget_$widget_id.load();	
})();

document.writeln("<div id='$widget_id' class='f9k_widget'>");
document.writeln("<img id='$widget_id_ld' src='$loader'>");
document.writeln("</div>");
