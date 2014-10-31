<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div id="photos-usage-message" class="pull-right">{{$usage}}</div>
		<h2>{{$pagename}}</h2>
		<div class="clear"></div>
	</div>

	<div class="section-content-wrapper">
		<form action="photos/{{$nickname}}" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form">
			<input type="hidden" id="photos-upload-source" name="source" value="photos" />
			<div id="photos-album-name-label" >
				<label id="photos-upload-newalbum-text" for="photos-upload-newalbum">{{$newalbum}}</label>
			</div>
			<div id="photos-album-name-input">
				<input id="photos-upload-newalbum" type="text" value="{{$selname}}" name="newalbum" list="dl-photo-upload" />
				<datalist id="dl-photo-upload">
				{{foreach $albums as $al}}
					{{if $al.text}}
					<option value="{{$al.text}}">
					{{/if}}
				{{/foreach}}
				</datalist>
			</div>

			<div id="photos-upload-new-end"></div>

			<div id="photos-upload-noshare-div" class="photos-upload-noshare-div" >
				<input id="photos-upload-noshare" type="checkbox" name="not_visible" value="1" />
				<label id="photos-upload-noshare-text" for="photos-upload-noshare" >{{$nosharetext}}</label>
			</div>

			<div id="photos-upload-perms" class="photos-upload-perms" >
				<span id="jot-perms-icon" class="icon-{{$lockstate}}" ></span>
				<button class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
			</div>

			{{$aclselect}}

			<div id="photos-upload-perms-end"></div>

			<div id="photos-upload-spacer"></div>

			{{$uploader}}

			{{$default}}

			<div class="photos-upload-end" ></div>
		</form>
	</div>
</div>
