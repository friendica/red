	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse-1">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<button id="expand-tabs" type="button" class="navbar-toggle" data-toggle="collapse" data-target="#tabs-collapse-1">
				<i class="icon-circle-arrow-down" id="expand-tabs-icon"></i>
			</button>
			<button id="expand-aside" type="button" class="navbar-toggle" data-toggle="offcanvas" data-target="#region_1">
				<i class="icon-circle-arrow-right" id="expand-aside-icon"></i>
			</button>
			{{if $userinfo}}
				<img class="dropdown-toggle fakelink" data-toggle="dropdown" id="avatar" src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"><span class="caret" id="usermenu-caret"></span>
				{{if $localuser}}
					<ul class="dropdown-menu" role="menu" aria-labelledby="avatar">
						{{foreach $nav.usermenu as $usermenu}}
						<li role="presentation"><a href="{{$usermenu.0}}" title="{{$usermenu.3}}" role="menuitem" id="{{$usermenu.4}}">{{$usermenu.1}}</a></li>
						{{/foreach}}
						<li role="presentation" class="divider"></li>
						{{if $nav.profiles}}<li role="presentation"><a href="{{$nav.profiles.0}}" title="{{$nav.profiles.3}}" role="menuitem" id="{{$nav.profiles.4}}">{{$nav.profiles.1}}</a></li>{{/if}}
						{{if $nav.settings}}<li role="presentation"><a href="{{$nav.settings.0}}" title="{{$nav.settings.3}}" role="menuitem" id="{{$nav.settings.4}}">{{$nav.settings.1}}</a></li>{{/if}}
						{{if $nav.manage}}<li role="presentation"><a href="{{$nav.manage.0}}" title="{{$nav.manage.3}}" role="menuitem" id="{{$nav.manage.4}}">{{$nav.manage.1}}</a></li>{{/if}}	
						{{if $nav.channels}}
						{{foreach $nav.channels as $chan}}
							<li role="presentation" class="nav-channel-select"><a href="manage/{{$chan.channel_id}}" title="{{$chan.channel_name}}" role="menuitem">{{$chan.channel_name}}</a></li>
						{{/foreach}}
						{{/if}}
						{{if $nav.admin}}
						<li role="presentation" class="divider"></li>
						<li role="presentation"><a href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" role="menuitem" id="{{$nav.admin.4}}">{{$nav.admin.1}}</a></li>
						{{/if}}
						{{if $nav.logout}}
						<li role="presentation" class="divider"></li>
						<li role="presentation"><a href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" role="menuitem" id="{{$nav.logout.4}}">{{$nav.logout.1}}</a></li>
						{{/if}}
					</ul>
				{{else}}
					{{if $nav.lock}}
						<ul class="dropdown-menu" role="menu" aria-labelledby="avatar">
							<li role="presentation"><a href="{{$nav.lock.0}}" title="{{$nav.lock.3}}" role="menuitem">{{$nav.lock.3}}</a></li>
						</ul>
					{{/if}}
				{{/if}}
			{{/if}}
		</div>
		<div class="collapse navbar-collapse" id="navbar-collapse-1">
			<ul class="nav navbar-nav navbar-left">
			{{if $nav.register}}<li class="{{$nav.register.2}}"><a href="{{$nav.register.0}}" title="{{$nav.register.3}}" id="{{$nav.register.4}}">{{$nav.register.1}}</a></li>{{/if}}
			{{if !$userinfo}}
			{{if $nav.loginmenu}}
				<li class="{{$nav.loginmenu.0.2}} hidden-xs">
					<a data-toggle="dropdown" data-target="#" href="{{$nav.loginmenu.0.0}}" title="{{$nav.loginmenu.0.3}}" id="{{$nav.loginmenu.0.4}}">{{$nav.loginmenu.0.1}} <span class="caret" id="loginmenu-caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						{{foreach $nav.loginmenu as $loginmenu}}
						<li role="presentation"><a class="{{$loginmenu.2}}" href="{{$loginmenu.0}}" title="{{$loginmenu.3}}" role="menuitem" id="{{$loginmenu.4}}">{{$loginmenu.1}}</a></li>
						{{/foreach}}
					</ul>
				</li>
				{{foreach $nav.loginmenu as $loginmenu}}
				<li role="presentation"><a class="{{$loginmenu.2}} visible-xs" href="{{$loginmenu.0}}" title="{{$loginmenu.3}}" role="menuitem">{{$loginmenu.1}}</a></li>
				{{/foreach}}
			{{/if}}
			{{/if}}

			{{if $nav.alogout}}<li class="{{$nav}}-alogout.2"><a href="{{$nav.alogout.0}}" title="{{$nav.alogout.3}}" id="{{$nav.alogout.4}}">{{$nav.alogout.1}}</a></li>{{/if}}

			{{if $nav.network}}
				<li class="{{$sel.network}} hidden-xs">
					<a href="{{$nav.network.0}}" title="{{$nav.network.3}}" id="{{$nav.network.4}}"><i class="icon-th"></i></a>
					<span class="net-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-network-menu"></span>
					<ul id="nav-network-menu" role="menu" class="dropdown-menu" rel="network">
						{{* <li id="nav-network-see-all"><a href="{{$nav.network.all.0}}">{{$nav.network.all.1}}</a></li> *}}
						<li id="nav-network-mark-all"><a href="#" onclick="markRead('network'); return false;">{{$nav.network.mark.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.network}} visible-xs">
					<a href="{{$nav.network.0}}" title="{{$nav.network.3}}" ><i class="icon-th"></i></a>
					<span class="net-update badge" rel="#nav-network-menu"></span>
				</li>
			{{/if}}

			{{if $nav.home}}
				<li class="{{$sel.home}} hidden-xs">
					<a class="{{$nav.home.2}}" href="{{$nav.home.0}}" title="{{$nav.home.3}}" id="{{$nav.home.4}}"><i class="icon-home"></i></a>
					<span class="home-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-home-menu"></span>
					<ul id="nav-home-menu" class="dropdown-menu" rel="home">
						{{* <li id="nav-home-see-all"><a href="{{$nav.home.all.0}}">{{$nav.home.all.1}}</a></li> *}}
						<li id="nav-home-mark-all"><a href="#" onclick="markRead('home'); return false;">{{$nav.home.mark.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.home}} visible-xs">
					<a class="{{$nav.home.2}}" href="{{$nav.home.0}}" title="{{$nav.home.3}}" ><i class="icon-home"></i></a>
					<span class="home-update badge" rel="#nav-home-menu"></span>
				</li>
			{{/if}}


			{{if $nav.messages}}
				<li class="{{$sel.messages}} hidden-xs">
					<a class="{{$nav.messages.2}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" id="{{$nav.messages.4}}"><i class="icon-envelope"></i></a>
					<span class="mail-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-messages-menu"></span>
					<ul id="nav-messages-menu" class="dropdown-menu" rel="messages">
						<li id="nav-messages-see-all"><a href="{{$nav.messages.all.0}}">{{$nav.messages.all.1}}</a></li>
						<li id="nav-messages-mark-all"><a href="#" onclick="markRead('messages'); return false;">{{$nav.messages.mark.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.messages}} visible-xs">
					<a class="{{$nav.messages.2}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" ><i class="icon-envelope"></i></a>
					<span class="mail-update badge" rel="#nav-messages-menu"></span>
				</li>
			{{/if}}

			{{if $nav.all_events}}
				<li class="{{$sel.all_events}} hidden-xs">
					<a class="{{$nav.all_events.2}}" href="{{$nav.all_events.0}}" title="{{$nav.all_events.3}}" id='{{$nav.all_events.4}}'><i class="icon-calendar"></i></a>
					<span class="all_events-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-all_events-menu"></span>
					<ul id="nav-all_events-menu" class="dropdown-menu" rel="all_events">
						<li id="nav-all_events-see-all"><a href="{{$nav.all_events.all.0}}">{{$nav.all_events.all.1}}</a></li>
						<li id="nav-all_events-mark-all"><a href="#" onclick="markRead('all_events'); return false;">{{$nav.all_events.mark.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.all_events}} visible-xs">
					<a class="{{$nav.all_events.2}}" href="{{$nav.all_events.0}}" title="{{$nav.all_events.3}}" ><i class="icon-calendar"></i></a>
					<span class="all_events-update badge" rel="#nav-all_events-menu"></span>
				</li>
			{{/if}}

			{{if $nav.intros}}
				<li class="{{$sel.intros}} hidden-xs">
					<a class="{{$nav.intros.2}}" href="{{$nav.intros.0}}" title="{{$nav.intros.3}}" id="{{$nav.intros.4}}"><i class="icon-user"></i></a>
					<span class="intro-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-intros-menu"></span>
					<ul id="nav-intros-menu" class="dropdown-menu" rel="intros">
						<li id="nav-intros-see-all"><a href="{{$nav.intros.all.0}}">{{$nav.intros.all.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.intros}} visible-xs">
					<a class="{{$nav.intros.2}}" href="{{$nav.intros.0}}" title="{{$nav.intros.3}}" ><i class="icon-user"></i></a>
					<span class="intro-update badge" rel="#nav-intros-menu"></span>
				</li>
			{{/if}}
		
			{{if $nav.notifications}}
				<li class="{{$sel.notifications}} hidden-xs">
					<a href="{{$nav.notifications.0}}" title="{{$nav.notifications.1}}" id="{{$nav.notifications.4}}"><i class="icon-exclamation"></i></a>
					<span class="notify-update badge dropdown-toggle" data-toggle="dropdown" rel="#nav-notify-menu"></span>
					<ul id="nav-notify-menu" class="dropdown-menu" rel="notify">
						<li id="nav-notify-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
						<li id="nav-notify-mark-all"><a href="#" onclick="markRead('notify'); return false;">{{$nav.notifications.mark.1}}</a></li>
						<li class="empty">{{$emptynotifications}}</li>
					</ul>
				</li>
				<li class="{{$sel.notifications}} visible-xs">
					<a href="{{$nav.notifications.0}}" title="{{$nav.notifications.1}}"><i class="icon-exclamation"></i></a>
					<span class="notify-update badge" rel="#nav-notify-menu"></span>
				</li>
			{{/if}}
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li class="hidden-xs">
					<form method="get" action="search" role="search">
						<div id="nav-search-spinner"></div><input class="icon-search" id="nav-search-text" type="text" value="" placeholder="&#xf002; {{$help}}" name="search" title="{{$nav.search.3}}" onclick="this.submit();"/>
					</form>
				</li>
				<li class="visible-xs">
					<a href="/search" title="{{$nav.search.3}}"><i class="icon-search"></i></a>
				</li>

			{{if $powered_by}}
				<div id="powered-by">{{$powered_by}}</div>
			{{/if}}
			{{if $nav.directory}}
				<li class="{{$sel.directory}}">
					<a class="{{$nav.directory.2}}" href="{{$nav.directory.0}}" title="{{$nav.directory.3}}" id="{{$nav.directory.4}}"><i class="icon-sitemap"></i></a>
				</li>
			{{/if}}

			{{if $nav.apps}}
				<li class="{{$sel.apps}}">
					<a class="{{$nav.apps.2}}" href="{{$nav.apps.0}}" title="{{$nav.apps.3}}" id="{{$nav.apps.4}}"><i class="icon-cogs"></i></a>
				</li>
			{{/if}}

			{{if $nav.help}}
				<li class="{{$sel.help}}">
					<a class="{{$nav.help.2}}" target="redmatrix-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}" id="{{$nav.help.4}}"><i class="icon-question"></i></a>
				</li>
			{{/if}}
			</ul>
		</div>
	</div>
