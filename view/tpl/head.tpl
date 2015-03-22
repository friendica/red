<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="{{$baseurl}}/" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, user-scalable={{$user_scalable}}" />
<meta name="generator" content="{{$generator}}" />

<!--[if IE]>
<script src="{{$baseurl}}/library/html5.js"></script>
<![endif]-->

{{$head_css}}

{{$js_strings}}

{{$head_js}}

<link rel="shortcut icon" href="{{$icon}}" />
<link rel="search"
         href="{{$baseurl}}/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in the Red Matrix" />


<script>

	var updateInterval = {{$update_interval}};
	var localUser = {{if $local_channel}}{{$local_channel}}{{else}}false{{/if}};
	var zid = {{if $zid}}'{{$zid}}'{{else}}null{{/if}};
	var justifiedGalleryActive = false;
	{{if $channel_hash}}var channelHash = '{{$channel_hash}}';{{/if}}
	{{if $channel_id}}var channelId = '{{$channel_id}}';{{/if}}{{* Used in e.g. autocomplete *}}

</script>



