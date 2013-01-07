<header>
	<div id="site-location">{{$sitelocation}}</div>
	<div id="banner">{{$banner}}</div>
</header>
<nav>
	<ul>
		{{if $nav.lock}}
			<li id="nav-rmagic-link" class="nav-menu-icon" >
				<a class="icon {{$nav.lock.2}}" href="{{$nav.lock.0}}" title="{{$nav.lock.3}}" >{{$nav.lock.1}}</a>
			</li>
		{{/if}}

		
		{{if $nav.network}}
			<li id="nav-network-link" class="nav-menu {{$sel.network}}">
				<a class="{{$nav.network.2}}" href="{{$nav.network.0}}" title="{{$nav.network.3}}" ><span class="icon network">{{$nav.network.1}}</span></a>
				<span id="net-update" class="nav-notify fakelink" onclick="notify_popup('network'); return false;" ></span>
			</li>
		{{/if}}

		{{if $nav.home}}
			<li id="nav-home-link" class="nav-menu {{$sel.home}}">
				<a class="{{$nav.home.2}}" href="{{$nav.home.0}}" title="{{$nav.home.3}}" ><span class="icon home">{{$nav.home.1}}</span></a>
				<span id="home-update" class="nav-notify fakelink" onclick="notify_popup('home'); return false;" ></span>
			</li>
		{{/if}}

		{{if $nav.messages}}
			<li id="nav-mail-link" class="nav-menu {{$sel.messages}}">
				<a class="{{$nav.messages.2}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" ><span class="icon mail">{{$nav.messages.1}}</span></a>
				<span id="mail-update" class="nav-notify fakelink" onclick="notify_popup('mail'); return false;" ></span>
			</li>
		{{/if}}

		{{if $nav.all_events}}
			<li id="nav-all-events-link" class="nav-menu {{$sel.all_events}}">
				<a class="{{$nav.all_events.2}}" href="{{$nav.all_events.0}}" title="{{$nav.all_events.3}}" ><span class="icon events">{{$nav.all_events.1}}</span></a>
				<span id="all-events-update" class="nav-notify fakelink" onclick="notify_popup('all_events'); return false;" ></span>
			</li>
		{{/if}}

		{{if $nav.intros}}
			<li id="nav-intros-link" class="nav-menu {{$sel.intros}}">
				<a class="{{$nav.intros.2}}" href="{{$nav.intros.0}}" title="{{$nav.intros.3}}" ><span class="icon introductions">{{$nav.intros.1}}</span></a>
				<span id="intro-update" class="nav-notify"></span>
			</li>
		{{/if}}
		

	{{if $nav.notifications}}

		<li id="nav-notifications-linkmenu" class="nav-menu fakelink" onclick="notify_popup('notify'); return false;" title="{{$nav.notifications.1}}"><span class="icon s22 notify">{{$nav.notifications.1}}</span></a>
			<span id="notify-update" class="nav-notify"></span>
			<ul id="nav-notifications-menu" class="menu-popup">
				<li id="nav-notifications-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
				<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">{{$nav.notifications.mark.1}}</a></li>
				<li class="empty">{{$emptynotifications}}</li>
			</ul>
		</li>
	{{/if}}		

	{{if $nav.settings}}
		<li id="nav-site-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-site-menu"><span class="icon s22 gear">{{$nav.settings.1}}</span></a>
			<ul id="nav-site-menu" class="menu-popup">
				{{if $nav.settings}}<li><a class="{{$nav.settings.2}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">{{$nav.settings.1}}</a></li>{{/if}}

				{{if $nav.admin}}<li><a class="{{$nav.admin.2}}" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" >{{$nav.admin.1}}</a></li>{{/if}}

			</ul>		
		</li>
	{{/if}}
		{{if $userinfo}}
			<li id="nav-user-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-user-menu" title="{{$userinfo.name}}"><img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"></a>
				{{if $localuser}}
				<ul id="nav-user-menu" class="menu-popup">
					{{foreach $nav.usermenu as $usermenu}}
						<li><a class="{{$usermenu.2}}" href="{{$usermenu.0}}" title="{{$usermenu.3}}">{{$usermenu.1}}</a></li>
					{{/foreach}}
				{{if $nav.profiles}}<li><a class="{{$nav.profiles.2}}" href="{{$nav.profiles.0}}" title="{{$nav.profiles.3}}">{{$nav.profiles.1}}</a></li>{{/if}}				
					{{if $nav.manage}}<li><a class="{{$nav.manage.2}}" href="{{$nav.manage.0}}" title="{{$nav.manage.3}}">{{$nav.manage.1}}</a></li>{{/if}}				

					{{if $nav.contacts}}<li><a class="{{$nav.contacts.2}}" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}" >{{$nav.contacts.1}}</a></li>{{/if}}	
				{{if $nav.logout}}<li><a class="menu-sep {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" >{{$nav.logout.1}}</a></li>{{/if}}

				</ul>
				{{/if}}
			</li>
		{{/if}}
		{{if $nav.login}}<li id="nav-login-link" class="nav-menu {{$nav.login.2}}"><a href="{{$nav.login.0}}" title="{{$nav.login.3}}" >{{$nav.login.1}}</a><li>{{/if}}

		{{if $nav.help}} 
		<li id="nav-help-link" class="nav-menu {{$sel.help}}">
			<a class="{{$nav.help.2}}" target="friendika-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}" >{{$nav.help.1}}</a>
		</li>
		{{/if}}

		{{if $nav.apps}}
			<li id="nav-apps-link" class="nav-menu {{$sel.apps}}">
				<a class=" {{$nav.apps.2}}" href="#" rel="#nav-apps-menu" title="{{$nav.apps.3}}" >{{$nav.apps.1}}</a>
				<ul id="nav-apps-menu" class="menu-popup">
					{{foreach $apps as $ap}}
					<li>{{$ap}}</li>
					{{/foreach}}
				</ul>
			</li>
		{{/if}}

		<li id="nav-searchbar">		
		<form method="get" action="search">
		<input id="nav-search-text" type="text" value="" placeholder="{{$nav.search.1}}" name="search" title="{{$nav.search.3}}" onclick="this.submit();" />
		</form>
		</li>




	</ul>


</nav>
<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li><a href="{0}"><img src="{1}">{2} <span class="notif-when">{3}</span></a></li>
</ul>

<div style="position: fixed; top: 3px; left: 5px; z-index:9999">{{$langselector}}</div>
<div id="panel" style="display: none;"></div>
