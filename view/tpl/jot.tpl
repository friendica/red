<div id="profile-jot-wrapper" >
	<form id="profile-jot-form" action="{{$action}}" method="post" >
		<input type="hidden" name="type" value="{{$ptyp}}" />
		<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
		<input type="hidden" name="return" value="{{$return_path}}" />
		<input type="hidden" name="location" id="jot-location" value="{{$defloc}}" />
		<input type="hidden" name="expire" id="jot-expire" value="{{$defexpire}}" />
		<input type="hidden" name="media_str" id="jot-media" value="" />
		<input type="hidden" name="source" id="jot-source" value="{{$source}}" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="post_id" value="{{$post_id}}" />
		<input type="hidden" name="webpage" value="{{$webpage}}" />
		<input type="hidden" name="preview" id="jot-preview" value="0" />
		{{if $showacl}}{{$acl}}{{/if}}
		{{$mimeselect}}
		{{$layoutselect}}
		{{if $id_select}}
			<div class="channel-id-select-div">
			<span class="channel-id-select-desc">{{$id_seltext}}</span> {{$id_select}}
			</div>
		{{/if}}
		<div id="jot-title-wrap">
			<input name="title" id="jot-title" type="text" placeholder="{{$placeholdertitle}}" value="{{$title}}" class="jothidden" style="display:none">
		</div>
		{{if $catsenabled}}
		<div id="jot-category-wrap">
			<input name="category" id="jot-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="jothidden" style="display:none" />
		</div>
		{{/if}}
		{{if $webpage}}
		<div id="jot-pagetitle-wrap">
			<input name="pagetitle" id="jot-pagetitle" type="text" placeholder="{{$placeholdpagetitle}}" value="{{$pagetitle}}" class="jothidden" style="display:none" />
		</div>
		{{/if}}
		<div id="jot-text-wrap">
			<textarea class="profile-jot-text" id="profile-jot-text" name="body" placeholder="{{$share}}">{{$content}}</textarea>
		</div>
		<div id="profile-jot-submit-wrapper" class="jothidden">
			<div id="profile-jot-submit-left" class="btn-group pull-left">
				{{if $visitor}}
				<button id="wall-image-upload" class="btn btn-default btn-sm" title="{{$upload}}" >
					<i class="icon-camera jot-icons"></i>
				</button>
				<button id="wall-file-upload" class="btn btn-default btn-sm" title="{{$attach}}" >
					<i id="wall-file-upload" class="icon-paper-clip jot-icons"></i>
				</button>
				<button id="profile-link-wrapper" class="btn btn-default btn-sm" title="{{$weblink}}" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);"  onclick="jotGetLink(); return false;">
					<i id="profile-link" class="icon-link jot-icons"></i>
				</button>
				<button id="profile-video-wrapper" class="btn btn-default btn-sm" title="{{$video}}" onclick="jotVideoURL();return false;">
					<i id="profile-video" class="icon-facetime-video jot-icons"></i>
				</button>
				<button id="profile-audio-wrapper" class="btn btn-default btn-sm" title="{{$audio}}" onclick="jotAudioURL();return false;">
					<i id="profile-audio" class="icon-volume-up jot-icons"></i>
				</button>
				<button id="profile-nolocation-wrapper" class="btn btn-default btn-sm" style="display: none;" title="{{$noloc}}" onclick="jotClearLocation();return false;">
					<i id="profile-nolocation" class="icon-circle-blank jot-icons"></i>
				</button>
				<button id="profile-location-wrapper" class="btn btn-default btn-sm" title="{{$setloc}}" onclick="jotGetLocation();return false;">
					<i id="profile-location" class="icon-globe jot-icons"></i>
				</button>
				{{/if}}
				{{if $feature_expire}}
				<button id="profile-expire-wrapper" class="btn btn-default btn-sm" title="{{$expires}}" onclick="jotGetExpiry();return false;">
					<i id="profile-expires" class="icon-eraser jot-icons"></i>
				</button>
				{{/if}}
				{{if $feature_encrypt}}
				<button id="profile-encrypt-wrapper" class="btn btn-default btn-sm" title="{{$encrypt}}" onclick="red_encrypt('{{$cipher}}','#profile-jot-text',$('#profile-jot-text').val());return false;">
					<i id="profile-encrypt" class="icon-key jot-icons"></i>
				</button>
				{{/if}}
			</div>
			<div id="profile-rotator-wrapper">
				<div id="profile-rotator"></div>
			</div>
			<div id="profile-jot-submit-right" class="btn-group pull-right">
				{{if $showacl}}
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" onclick="return false;">
					<i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons">{{$bang}}</i>
				</button>
				{{/if}}
				{{if $preview}}
				<button class="btn btn-default btn-sm" onclick="preview_post();return false;" title="{{$preview}}">
					<i class="icon-eye-open jot-icons" ></i>
				</button>
				{{/if}}
				<button class="btn btn-primary btn-sm" type="submit" name="submit">{{$share}}</button>
			</div>
			<div id="profile-jot-perms-end"></div>
			<div id="profile-jot-plugin-wrapper">
				{{$jotplugins}}
			</div>
		</div>
		<div id="profile-jot-text-loading"></div>
		<div id="profile-jot-end" class="clear"></div>
		<div id="jot-preview-content" style="display:none;"></div>
	</form>
</div>

<!-- Modal for item expiry-->
<div class="modal" id="expiryModal" tabindex="-1" role="dialog" aria-labelledby="expiryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="expiryModalLabel">{{$expires}}</h4>
      </div>
     <!--  <div class="modal-body"> -->
            <div class="modal-body form-group" style="width:90%">
                <div class="input-group input-group-sm date" id="datetimepicker1">
                    <span class="input-group-addon"><!-- <span class="glyphicon glyphicon-calendar"></span> -->
                    <span class="icon-calendar"></span>
                    </span>
                    <input id="expiration-date" type='text' class="form-control" data-format="YYYY-MM-DD HH:mm" size="20"/>
                </div>
            </div>
      <!-- </div> -->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{$expiryModalCANCEL}}</button>
        <button id="expiry-modal-OKButton" type="button" class="btn btn-primary">{{$expiryModalOK}}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
  $(function() {
    $('#datetimepicker1').datetimepicker({
      language: 'us',
      icons: {
					time: "icon-time",
					date: "icon-calendar",
					up: "icon-arrow-up",
					down: "icon-arrow-down"
				}
    });
  });
</script>

{{if $content}}
<script>initEditor();</script>
{{/if}}
