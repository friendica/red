<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="{{$baseurl}}/" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, user-scalable=0">
<meta name="generator" content="{{$generator}}" />

<!--[if IE]>
<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<script>(function(w){var dpr=((w.devicePixelRatio===undefined)?1:w.devicePixelRatio);if(!!w.navigator.standalone){var r=new XMLHttpRequest();r.open('GET','/retinaimages.php?devicePixelRatio='+dpr,false);r.send()}else{document.cookie='devicePixelRatio='+dpr+'; path=/'}})(window)</script>
<noscript><style id="devicePixelRatio" media="only screen and (-moz-min-device-pixel-ratio: 2), only screen and (-o-min-device-pixel-ratio: 2/1), only screen and (-webkit-min-device-pixel-ratio: 2), only screen and (min-device-pixel-ratio: 2)">html{background-image:url("/retinaimages.php?devicePixelRatio=2")}</style></noscript>

{{$head_css}}

{{$js_strings}}

{{$head_js}}

<link rel="shortcut icon" href="{{$icon}}" />
<link rel="search"
         href="{{$baseurl}}/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in Red" />


<script>

	var updateInterval = {{$update_interval}};
	var localUser = {{if $local_user}}{{$local_user}}{{else}}false{{/if}};
	var zid = {{if $zid}}'{{$zid}}'{{else}}null{{/if}};

</script>



