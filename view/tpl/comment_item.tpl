		{{if $threaded}}
		<div class="comment-wwedit-wrapper threaded" id="comment-edit-wrapper-{{$id}}" style="display: block;">
		{{else}}
		<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
		{{/if}}
			<form class="comment-edit-form" style="display: block;" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
				<input type="hidden" name="type" value="{{$type}}" />
				<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
				<input type="hidden" name="parent" value="{{$parent}}" />
				<input type="hidden" name="return" value="{{$return_path}}" />
				<input type="hidden" name="jsreload" value="{{$jsreload}}" />
				<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" onFocus="commentOpenUI(this,{{$id}});" onBlur="commentCloseUI(this,{{$id}});" >{{$comment}}</textarea>
				{{if $qcomment}}
					<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});" >
					<option value=""></option>
				{{foreach $qcomment as $qc}}
					<option value="{{$qc}}">{{$qc}}</option>				
				{{/foreach}}
					</select>
				{{/if}}
				<div class="clear"></div>
				<div id="comment-tools-{{$id}}" class="comment-tools">
					<div id="comment-edit-bb-{{$id}}" class="btn-toolbar pull-left">
						<div class='btn-group'>
							<button class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('{{$comment}}','b', {{$id}}); return false;">
								<i class="icon-bold comment-icon"></i>
							</button>
							<button class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('{{$comment}}','i', {{$id}}); return false;">
								<i class="icon-italic comment-icon"></i>
							</button>
							<button class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('{{$comment}}','u', {{$id}}); return false;">
								<i class="icon-underline comment-icon"></i>
							</button>
							<button class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('{{$comment}}','quote', {{$id}}); return false;">
								<i class="icon-quote-left comment-icon"></i>
							</button>
							<button class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('{{$comment}}','code', {{$id}}); return false;">
								<i class="icon-terminal comment-icon"></i>
							</button>
						</div>
						<div class='btn-group'>
							<!--button class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('{{$comment}}','img', {{$id}}); return false;">
								<i class="icon-camera comment-icon"></i>
							</button-->
							<button class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertCommentURL('{{$comment}}',{{$id}}); return false;">
								<i class="icon-link comment-icon"></i>
							</button>
							<!--button class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('{{$comment}}','video', {{$id}}); return false;">
								<i class="icon-facetime-video comment-icon"></i>
							</button-->
						</div>
						{{if $feature_encrypt}}
						<div class='btn-group'>
							<button class="btn btn-default btn-xs" title="{{$encrypt}}" onclick="red_encrypt('{{$cipher}}','#comment-edit-text-' + '{{$id}}',''); return false;">
								<i class="icon-key comment-icon"></i>
							</button>
						</div>
						{{/if}}
					</div>
					<div class="btn-group pull-right" id="comment-edit-submit-wrapper-{{$id}}">
						{{if $preview}}
						<button id="comment-edit-presubmit-{{$id}}" class="btn btn-default btn-xs" onclick="preview_comment({{$id}}); return false;" title="{{$preview}}">
							<i class="icon-eye-open comment-icon" ></i>
						</button>
						{{/if}}
						<button id="comment-edit-submit-{{$id}}" class="btn btn-primary btn-xs" type="submit" name="button-submit" onclick="post_comment({{$id}}); return false;">{{$submit}}</button>
					</div>
				</div>
				<div class="clear"></div>
			</form>
		</div>
