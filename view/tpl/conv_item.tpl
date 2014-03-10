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
			{{* comment out for now. let's see if somebody is missing it. if yes we need a better visual concept.
			{{if $item.owner_url}}
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
				<img src="{{$item.owner_photo}}" class="wall-item-photo{{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" alt="{{$item.owner_name}}" /></a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="{{$item.wall}}" /></div>
			{{/if}}
			*}}
			<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}}" id="wall-item-photo-wrapper-{{$item.id}}" 
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
                onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}">
				<img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" /></a>
				<span onclick="openClose('wall-item-photo-menu-{{$item.id}}');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-{{$item.id}}">menu</span>
                <div class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">
                    <ul>
                        {{$item.item_photo_menu}}
                    </ul>
                </div>

			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-wrapper" id="wall-item-wrapper-{{$item.id}}" >
				{{if $item.lock}}<i class="wall-item-lock icon-lock lockview" title="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" ></i>
				{{else}}<div class="wall-item-lock"></div>{{/if}}	
			</div>
		</div>
		<div class="wall-item-author">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}} {{$item.via}} <a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}<br />

				<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}">{{if $item.verified}}<i class="icon-ok" title="{{$item.verified}}"></i>&nbsp;{{/if}}{{if $item.location}}<span class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}},&nbsp;</span>{{/if}}<span class="autotime" title="{{$item.isotime}}">{{$item.localtime}}{{if $item.editedtime}} {{$item.editedtime}}{{/if}}{{if $item.expiretime}} {{$item.expiretime}}{{/if}}</span>{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}</div>
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
				<i class="icon-thumbs-up-alt item-tool" title="{{$item.like.0}}" onclick="dolike({{$item.id}},'like'); return false"></i>
			{{/if}}
			{{if $item.dislike}}
				<i class="icon-thumbs-down-alt item-tool" title="{{$item.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false"></i>
			{{/if}}
			{{if $item.share}}
				<i class="icon-retweet item-tool" title="{{$item.share.0}}" onclick="jotShare({{$item.id}}); return false"></i>
			{{/if}}
			{{if $item.plink}}
				<a href="{{$item.plink.href}}" title="{{$item.plink.title}}" ><i class="icon-external-link item-tool"></i></a>
			{{/if}}
			{{if $item.edpost}}
				<a href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"><i class="editpost icon-pencil item-tool"></i></a>
			{{/if}}			 
			{{if $item.star}}
			<i id="starred-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="star-item item-tool {{$item.star.isstarred}}" title="{{$item.star.toggle}}"></i>
			{{/if}}
			{{if $item.tagger}}
			<i id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="tag-item icon-tag item-tool" title="{{$item.tagger.tagit}}"></i>
			{{/if}}
			{{if $item.filer}}
			<i id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item icon-folder-open item-tool" title="{{$item.filer}}"></i>
			{{/if}}
			{{if $item.bookmark}}
			<i id="bookmarker-{{$item.id}}" onclick="itemBookmark({{$item.id}}); return false;" class="bookmark-item icon-bookmark item-tool" title="{{$item.bookmark}}"></i>
			{{/if}}			
			<div id="like-rotator-{{$item.id}}" class="like-rotator"></div>

			<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-{{$item.id}}" >
				{{if $item.drop.dropping}}<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" title="{{$item.drop.delete}}" ><i class="icon-remove drop-icons item-tool"></i></a>{{/if}}
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
