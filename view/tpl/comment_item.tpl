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

				<div class="comment-edit-photo" id="comment-edit-photo-{{$id}}" >
					<a class="comment-edit-photo-link" href="{{$mylink}}" title="{{$mytitle}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle}}" title="{{$mytitle}}" /></a>
				</div>
				<div class="comment-edit-photo-end"></div>
				<ul class="comment-edit-bb-{{$id}}">
					<li><i class="icon-bold shadow comment-icon"
						style="cursor: pointer;" title="{{$edbold}}"
						onclick="insertbbcomment('{{$comment}}','b', {{$id}});"></i></li>
					<li><i class="icon-italic shadow comment-icon"
						style="cursor: pointer;" title="{{$editalic}}"
						onclick="insertbbcomment('{{$comment}}','i', {{$id}});"></i></li>
					<li><i class="icon-underline shadow comment-icon"
						style="cursor: pointer;" title="{{$eduline}}"
						onclick="insertbbcomment('{{$comment}}','u', {{$id}});"></i></li>
					<li><i class="icon-quote-left shadow comment-icon"
						style="cursor: pointer;" title="{{$edquote}}"
						onclick="insertbbcomment('{{$comment}}','quote', {{$id}});"></i></li>
					<li><i class="icon-terminal shadow comment-icon"
						style="cursor: pointer;" title="{{$edcode}}"
						onclick="insertbbcomment('{{$comment}}','code', {{$id}});"></i></li>
					<li><i class="icon-camera shadow comment-icon"
						style="cursor: pointer;" title="{{$edimg}}"
						onclick="insertbbcomment('{{$comment}}','img', {{$id}});"></i></li>
					<li><i class="icon-link shadow comment-icon"
						style="cursor: pointer;" title="{{$edurl}}"
						onclick="insertbbcomment('{{$comment}}','url', {{$id}});"></i></li>
					<li><i class="icon-facetime-video shadow comment-icon"
						style="cursor: pointer;" title="{{$edvideo}}"
						onclick="insertbbcomment('{{$comment}}','video', {{$id}});"></i></li>
					{{if $feature_encrypt}}
						<li><i class="icon-key shadow comment-icon"
							style="cursor: pointer;" title="{{$encrypt}}"
							onclick="red_encrypt('{{$cipher}}','#comment-edit-text-' + '{{$id}}',''); return false;"></i></li>
					{{/if}}
				</ul>	
				<div class="comment-edit-bb-end"></div>
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" onFocus="commentOpen(this,{{$id}});cmtBbOpen(this, {{$id}});" onBlur="commentClose(this,{{$id}});cmtBbClose(this,{{$id}});" >{{$comment}}</textarea>			
				{{if $qcomment}}
					<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});" >
					<option value=""></option>
				{{foreach $qcomment as $qc}}
					<option value="{{$qc}}">{{$qc}}</option>				
				{{/foreach}}
					</select>
				{{/if}}

				<div class="comment-edit-text-end"></div>
				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;" >
					<input type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" class="comment-edit-submit" name="submit" value="{{$submit}}" />
					{{if $preview}}
					<span onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" class="fakelink">{{$preview}}</span>
					<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
					{{/if}}
				</div>

				<div class="comment-edit-end"></div>
			</form>

		</div>
