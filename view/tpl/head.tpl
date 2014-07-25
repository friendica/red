<base href="{{$baseurl}}/" />
<META NAME="description" CONTENT="The RedMatrix is a super network created from a huge number of smaller independent and autonomous websites - which are linked together...">
<META NAME="keywords" CONTENT="network,matrix,social,communications,social networking,content ownership,privacy and content ownership">
<META NAME="robot" CONTENT="index,follow">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, user-scalable=0" />
<meta name="generator" content="{{$generator}}" />

<!--[if IE]>
<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

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



