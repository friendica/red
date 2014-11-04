<div id="photo-upload-form" class="generic-content-wrapper">
	<div class="section-content-tools-wrapper">
		<form action="photos/{{$nickname}}" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form">
			<input type="hidden" id="photos-upload-source" name="source" value="photos" />

			<div class="form-group">
				<label for="photos-upload-album">{{$newalbum_label}}</label>
				<input type="text" class="form-control" id="photos-upload-album" name="newalbum" placeholder="{{$newalbum_placeholder}}" value="{{$selname}}" list="dl-photo-upload">
				<datalist id="dl-photo-upload">
				{{foreach $albums as $al}}
					{{if $al.text}}
					<option value="{{$al.text}}">
					{{/if}}
				{{/foreach}}
				</datalist>
			</div>

			{{$aclselect}}

			{{if $default}}
			<div class="pull-left">
				<input id="photos-upload-choose" type="file" name="userfile" />
			</div>
			<div class="btn-group pull-right">
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
					<i id="jot-perms-icon" class="icon-{{$lockstate}}"></i>
				</button>
				<button class="btn btn-primary btn-sm" type="submit" name="submit" id="photos-upload-submit">{{$submit}}</button>
			</div>
			{{/if}}

			<div id="photos-upload-new-end" class="clear"></div>

			<div class="checkbox pull-left">
				<label class="checkbox-inline" for="photos-upload-noshare" >
					<input class="checkbox-inline" id="photos-upload-noshare" type="checkbox" name="not_visible" value="1" />{{$nosharetext}}
				</label>
			</div>

			{{if $uploader}}
			<div id="photos-upload-perms" class="pull-right">
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
					<i id="jot-perms-icon" class="icon-{{$lockstate}}"></i>
				</button>
				<div class="pull-right">
					{{$uploader}}
				</div>
			</div>
			{{/if}}
		</form>
	</div>
	<div class="photos-upload-end" class="clear"></div>
</div>
