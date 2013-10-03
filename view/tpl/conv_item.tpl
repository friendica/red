{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
	<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">{{$item.num_comments}}</span> <span id="hide-comments-{{$item.id}}" class="hide-comments fakelink" onclick="showHideComments({{$item.id}});">{{$item.hide_text}}</span>
	</div>
	<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}
<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper {{$item.toplevel}}">
<a name="{{$item.id}}" ></a>
<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-outside-wrapper-{{$item.id}}" >
	<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}" >
		<div class="wall-item-info{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-info-{{$item.id}}">
			{{if $item.owner_url}}
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
				<img src="{{$item.owner_photo}}" class="wall-item-photo{{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.owner_name}}" /></a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="{{$item.wall}}" /></div>
			{{/if}}
			<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}}" id="wall-item-photo-wrapper-{{$item.id}}" 
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
                onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}">
				<img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.name}}" /></a>
				<span onclick="openClose('wall-item-photo-menu-{{$item.id}}');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-{{$item.id}}">menu</span>
                <div class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">
                    <ul>
                        {{$item.item_photo_menu}}
                    </ul>
                </div>

			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-wrapper" id="wall-item-wrapper-{{$item.id}}" >
				{{if $item.lock}}<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" /></div>
				{{else}}<div class="wall-item-lock"></div>{{/if}}	
				<div class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}}</div>
				{{if $item.verified}}<img src="images/lock_icon.gif" alt="{{$item.verified}}" title="{{$item.verified}}" height="10" width="10" />{{/if}}
				{{if $item.unverified}}<img src="images/unlock_icon.gif" alt="{{$item.unverified}}" title="{{$item.unverified}}" height="10" width="10" />{{/if}}
			</div>
		</div>
		<div class="wall-item-author">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}} {{$item.to}} <a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a> {{$item.vwall}}{{/if}}<br />
				<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}"><span class="autotime" title="{{$item.isotime}}">{{$item.localtime}}</span>{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}</div>
		</div>			
		<div class="wall-item-content" id="wall-item-content-{{$item.id}}" >
			<div class="wall-item-title" id="wall-item-title-{{$item.id}}">{{$item.title}}</div>
			<div class="wall-item-title-end"></div>
			<div class="wall-item-body" id="wall-item-body-{{$item.id}}" >{{$item.body}}
					<div class="body-tag">
						{{foreach $item.tags as $tag}}
							<span class='tag'>{{$tag}}</span>
						{{/foreach}}
					</div>
			{{if $item.has_cats}}
			<div class="categorytags"><span>{{$item.txt_cats}} {{foreach $item.categories as $cat}}{{$cat.name}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}

			{{if $item.has_folders}}
			<div class="filesavetags"><span>{{$item.txt_folders}} {{foreach $item.folders as $cat}}{{$cat.name}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}
			</div>
		</div>
		<div class="wall-item-tools" id="wall-item-tools-{{$item.id}}">
			{{if $item.like}}
				<a href="#" class="icon like item-tool" title="{{$item.like.0}}" onclick="dolike({{$item.id}},'like'); return false"></a>
			{{/if}}
			{{if $item.dislike}}
				<a href="#" class="icon dislike item-tool" title="{{$item.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false"></a>
			{{/if}}
			{{if $item.share}}
				<a href="#" class="icon recycle item-tool" title="{{$item.share.0}}" onclick="jotShare({{$item.id}}); return false"></a>
			{{/if}}
			{{if $item.plink}}
				<a href="{{$item.plink.href}}" title="{{$item.plink.title}}" target="external-link" class="icon item-tool remote-link{{$item.sparkle}}"></a>
			{{/if}}
			{{if $item.edpost}}
				<a class="editpost icon pencil item-tool" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
			{{/if}}			 
			{{if $item.star}}
			<a href="#" id="starred-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="star-item icon item-tool {{$item.star.isstarred}}" title="{{$item.star.toggle}}"></a>
			{{/if}}
			{{if $item.tagger}}
			<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="tag-item icon item-tool tagged" title="{{$item.tagger.tagit}}"></a>
			{{/if}}
			{{if $item.filer}}
			<a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon item-tool" title="{{$item.filer}}"></a>
			{{/if}}			
			<div id="like-rotator-{{$item.id}}" class="like-rotator"></div>

			<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-{{$item.id}}" >
				{{if $item.drop.dropping}}<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon drophide" title="{{$item.drop.delete}}" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a>{{/if}}
			</div>
				{{if $item.drop.pagedrop}}<input type="checkbox" onclick="checkboxhighlight(this);" title="{{$item.drop.select}}" class="item-select" name="itemselected[]" value="{{$item.id}}" />{{/if}}
			<div class="wall-item-delete-end"></div>
			<div class="wall-item-like {{$item.indent}}" id="wall-item-like-{{$item.id}}">{{$item.showlike}}</div>
			<div class="wall-item-dislike {{$item.indent}}" id="wall-item-dislike-{{$item.id}}">{{$item.showdislike}}</div>

		</div>
	</div>	

	<div class="wall-item-wrapper-end"></div>

<div class="wall-item-outside-wrapper-end {{$item.indent}}" ></div>
</div>
{{if $item.toplevel}}
{{foreach $item.children as $child}}
	{{include file="{{$child.template}}" item=$child}}
{{/foreach}}
{{/if}}

<div class="wall-item-comment-wrapper" >
	{{$item.comment}}
</div>


</div>
{{if $item.comment_lastcollapsed}}</div>{{/if}}
