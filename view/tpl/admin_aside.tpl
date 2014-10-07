<script>
	// update pending count //
	$(function(){

		$("nav").bind('nav-update',  function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { reigster=""; elm.hide();} else { elm.show(); }
			elm.html(register);
		});
	});
</script>
<h3>{{$admtxt}}</h3>
<ul class="nav nav-pills nav-stacked">
	<li><a href='{{$admin.site.0}}'>{{$admin.site.1}}</a></li>
	<li><a href='{{$admin.users.0}}'>{{$admin.users.1}}<span id='pending-update' title='{{$h_pending}}'></span></a></li>
	<li><a href='{{$admin.channels.0}}'>{{$admin.channels.1}}</a></li>
	<li><a href='{{$admin.plugins.0}}'>{{$admin.plugins.1}}</a></li>
	<li><a href='{{$admin.themes.0}}'>{{$admin.themes.1}}</a></li>
	<li><a href='{{$admin.hubloc.0}}'>{{$admin.hubloc.1}}</a></li>
	<li><a href='{{$admin.dbsync.0}}'>{{$admin.dbsync.1}}</a></li>
</ul>

{{if $admin.update}}
<ul class="nav nav-pills nav-stacked">
	<li><a href='{{$admin.update.0}}'>{{$admin.update.1}}</a></li>
	<li><a href='https://kakste.com/profile/inthegit'>Important Changes</a></li>
</ul>
{{/if}}


{{if $admin.plugins_admin}}<h3>{{$plugadmtxt}}</h3>{{/if}}
<ul class="nav nav-pills nav-stacked">
	{{foreach $admin.plugins_admin as $l}}
	<li><a href='{{$l.0}}'>{{$l.1}}</a></li>
	{{/foreach}}
</ul>
	
	
<h3>{{$logtxt}}</h3>
<ul class="nav nav-pills nav-stacked">
	<li><a href='{{$admin.logs.0}}'>{{$admin.logs.1}}</a></li>
</ul>

