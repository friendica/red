<div id="remote-friends-in-common" class="bigwidget">
	<div id="rfic-desc">{{$desc}} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{if $linkmore}}<a href="{{$base}}/common/{{$uid}}">{{$more}}</a>{{/if}}</div>
	{{if $items}}
	{{foreach $items as $item}}
	<div class="profile-match-wrapper">
		<div class="profile-match-photo">
			<a href="{{$base}}/chanview?f=&url={{$item.xchan_url}}">
				<img src="{{$item.xchan_photo_m}}" width="80" height="80" alt="{{$item.xchan_name}}" title="{{$item.xchan_name}}" />
			</a>
		</div>
		<div class="profile-match-break"></div>
		<div class="profile-match-name">
			<a href="{{$base}}/chanview?f=&url={{$item.xchan_url}}" title="{{$item.xchan_name}}">{{$item.xchan_name}}</a>
		</div>
		<div class="profile-match-end"></div>
	</div>
	{{/foreach}}
	{{/if}}
	<div id="rfic-end" class="clear"></div>
</div>

