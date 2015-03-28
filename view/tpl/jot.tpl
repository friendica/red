<div id="profile-jot-wrapper">
	<form id="profile-jot-form" action="{{$action}}" method="post">
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
		<input type="hidden" name="preview" id="jot-preview" value="0" />		<input type="hidden" id="jot-consensus" name="consensus" value="{{if $consensus}}{{$consensus}}{{else}}0{{/if}}" />
		{{if $showacl}}{{$acl}}{{/if}}
		{{$mimeselect}}
		{{$layoutselect}}
		{{if $id_select}}
		<div class="channel-id-select-div">
			<span class="channel-id-select-desc">{{$id_seltext}}</span> {{$id_select}}
		</div>
		{{/if}}
		<div id="jot-title-wrap" class="jothidden" style="display:none">
			<input name="title" id="jot-title" type="text" placeholder="{{$placeholdertitle}}" tabindex=1 value="{{$title}}">
		</div>
		{{if $catsenabled}}
		<div id="jot-category-wrap" class="jothidden" style="display:none">
			<input name="category" id="jot-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" data-role="cat-tagsinput">
		</div>
		{{/if}}
		{{if $webpage}}
		<div id="jot-pagetitle-wrap" class="jothidden" style="display:none">
			<input name="pagetitle" id="jot-pagetitle" type="text" placeholder="{{$placeholdpagetitle}}" value="{{$pagetitle}}">
		</div>
		{{/if}}
		<div id="jot-text-wrap">
			<textarea class="profile-jot-text" id="profile-jot-text" name="body" tabindex=2 placeholder="{{$share}}">{{$content}}</textarea>
		</div>
		<div id="profile-jot-submit-wrapper" class="jothidden">
			<div id="profile-jot-submit-left" class="btn-toolbar pull-left">
				<div class="btn-group">
					<button id="main-editor-bold" class="btn btn-default btn-sm" title="{{$bold}}" onclick="inserteditortag('b'); return false;">
						<i class="icon-bold jot-icons"></i>
					</button>
					<button id="main-editor-italic" class="btn btn-default btn-sm" title="{{$italic}}" onclick="inserteditortag('i'); return false;">
						<i class="icon-italic jot-icons"></i>
					</button>
					<button id="main-editor-underline" class="btn btn-default btn-sm" title="{{$underline}}" onclick="inserteditortag('u'); return false;">
						<i class="icon-underline jot-icons"></i>
					</button>
					<button id="main-editor-quote" class="btn btn-default btn-sm" title="{{$quote}}" onclick="inserteditortag('quote'); return false;">
						<i class="icon-quote-left jot-icons"></i>
					</button>
					<button id="main-editor-code" class="btn btn-default btn-sm" title="{{$code}}" onclick="inserteditortag('code'); return false;">
						<i class="icon-terminal jot-icons"></i>
					</button>
				</div>
				{{if $visitor}}
				<div class="btn-group hidden-xs">
					{{if $writefiles}}
					<button id="wall-file-upload" class="btn btn-default btn-sm" title="{{$attach}}" >
						<i id="wall-file-upload-icon" class="icon-paper-clip jot-icons"></i>
					</button>
					{{/if}}
					<button id="profile-link-wrapper" class="btn btn-default btn-sm" title="{{$weblink}}" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);"  onclick="jotGetLink(); return false;">
						<i id="profile-link" class="icon-link jot-icons"></i>
					</button>
				</div>
				<div class="btn-group hidden-xs hidden-sm">
					<button id="profile-location-wrapper" class="btn btn-default btn-sm" title="{{$setloc}}" onclick="jotGetLocation();return false;">
						<i id="profile-location" class="icon-globe jot-icons"></i>
					</button>
					{{if $noloc}}
					<button id="profile-nolocation-wrapper" class="btn btn-default btn-sm" title="{{$noloc}}" onclick="jotClearLocation();return false;" disabled="disabled">
						<i id="profile-nolocation" class="icon-circle-blank jot-icons"></i>
					</button>
					{{/if}}
				{{else}}
				<div class="btn-group hidden-xs">
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
				{{if $feature_voting}}
					<button id="profile-voting-wrapper" class="btn btn-default btn-sm" title="{{$voting}}" onclick="toggleVoting();return false;">
						<i id="profile-voting" class="icon-check-empty jot-icons"></i>
					</button>
				{{/if}}
				</div>
				<div class="btn-group visible-xs visible-sm">
					<button type="button" id="more-tools" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<i id="more-tools-icon" class="icon-caret-down jot-icons"></i>
					</button>
					<ul class="dropdown-menu dropdown-menu-right" role="menu">
						<li class="visible-xs"><a href="#" onclick="preview_post();return false;"><i class="icon-eye-open"></i>&nbsp;{{$preview}}</a></li>
						{{if $visitor}}
						<li class="divider visible-xs"></li>
						{{if $writefiles}}<li class="visible-xs"><a id="wall-file-upload-sub" href="#" ><i class="icon-paper-clip"></i>&nbsp;{{$attach}}</a></li>{{/if}}
						<li class="visible-xs"><a href="#" onclick="jotGetLink(); return false;"><i class="icon-link"></i>&nbsp;{{$weblink}}</a></li>
						<!--li class="visible-xs"><a href="#" onclick="jotVideoURL(); return false;"><i class="icon-facetime-video"></i>&nbsp;{{$video}}</a></li-->
						<!--li class="visible-xs"><a href="#" onclick="jotAudioURL(); return false;"><i class="icon-volume-up"></i>&nbsp;{{$audio}}</a></li-->
						{{/if}}
						<li class="divider visible-xs"></li>
						<li class="visible-xs visible-sm"><a href="#" onclick="jotGetLocation(); return false;"><i class="icon-globe"></i>&nbsp;{{$setloc}}</a></li>
						{{if $noloc}}
						<li class="visible-xs visible-sm"><a href="#" onclick="jotClearLocation(); return false;"><i class="icon-circle-blank"></i>&nbsp;{{$noloc}}</a></li>
						{{/if}}
						{{if $feature_expire}}
						<li class="visible-xs visible-sm"><a href="#" onclick="jotGetExpiry(); return false;"><i class="icon-eraser"></i>&nbsp;{{$expires}}</a></li>
						{{/if}}
						{{if $feature_encrypt}}
						<li class="visible-xs visible-sm"><a href="#" onclick="red_encrypt('{{$cipher}}','#profile-jot-text',$('#profile-jot-text').val());return false;"><i class="icon-key"></i>&nbsp;{{$encrypt}}</a></li>
						{{/if}}
						{{if $feature_voting}}
						<li class="visible-xs visible-sm"><a href="#" onclick="toggleVoting(); return false;"><i id="profile-voting-sub" class="icon-check-empty"></i>&nbsp;{{$voting}}</a></li>
						{{/if}}
					</ul>
				</div>
			</div>
			<div id="profile-rotator-wrapper">
				<div id="profile-rotator"></div>
			</div>
			<div id="profile-jot-submit-right" class="btn-group pull-right">
				{{if $showacl}}
				<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" onclick="return false;">
					<i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons"></i>{{if $bang}}&nbsp;<i class="icon-exclamation jot-icons"></i>{{/if}}
				</button>
				{{/if}}
				{{if $preview}}
				<button class="btn btn-default btn-sm hidden-xs" onclick="preview_post();return false;" title="{{$preview}}">
					<i class="icon-eye-open jot-icons" ></i>
				</button>
				{{/if}}
				<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" tabindex=3 name="button-submit" >{{$share}}</button>
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
		<div class='date'><input type='text' placeholder='yyyy-mm-dd HH:MM' name='start_text' id='expiration-date' class="form-control" /></div><script type='text/javascript'>$(function () {var picker = $('#expiration-date').datetimepicker({format:'Y-m-d H:i', minDate: 0 }); })</script>
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
