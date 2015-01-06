{{if $item.comment_firstcollapsed}}
<div class="hide-comments-outer fakelink" onclick="showHideComments({{$item.id}});">
	<span id="hide-comments-{{$item.id}}" class="hide-comments">{{$item.hide_text}}</span>&nbsp;<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">{{$item.num_comments}}</span>
</div>
<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}
	<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper {{$item.toplevel}} conv-list-mode">
		<a name="{{$item.id}}" ></a>
		<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}" id="wall-item-outside-wrapper-{{$item.id}}" >
			<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}" style="clear:both;">
				<div class="wall-item-info" id="wall-item-info-{{$item.id}}" >
					<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}}" id="wall-item-photo-wrapper-{{$item.id}}">
						<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}"><img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" /></a>
					</div>
					<div class="wall-item-photo-end" style="clear:both"></div>
				</div>
				{{if $item.title}}
				<div class="wall-item-title" id="wall-item-title-{{$item.id}}">
				<h3>{{if $item.title_tosource}}{{if $item.plink}}<a href="{{$item.plink.href}}" title="{{$item.title}} ({{$item.plink.title}})">{{/if}}{{/if}}{{$item.title}}{{if $item.title_tosource}}{{if $item.plink}}</a>{{/if}}{{/if}}</h3>
				</div>
				{{/if}}
				{{if $item.lock}}
				<div class="wall-item-lock dropdown">
					<i class="icon-lock lockview dropdown-toggle" data-toggle="dropdown" title="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" ></i><ul id="panel-{{$item.id}}" class="lockview-panel dropdown-menu"></ul>&nbsp;
				</div>
				{{/if}}
				<div class="wall-item-author">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}}&nbsp;{{$item.via}}&nbsp;<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
				</div>
				<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}">
					{{if $item.verified}}<i class="icon-ok item-verified" title="{{$item.verified}}"></i>&nbsp;{{elseif $item.forged}}<i class="icon-remove item-forged" title="{{$item.forged}}"></i>&nbsp;{{/if}}{{if $item.location}}<span class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}},&nbsp;</span>{{/if}}<span class="autotime" title="{{$item.isotime}}">{{$item.localtime}}{{if $item.editedtime}}&nbsp;{{$item.editedtime}}{{/if}}{{if $item.expiretime}}&nbsp;{{$item.expiretime}}{{/if}}</span>{{if $item.editedtime}}&nbsp;<i class="icon-pencil"></i>{{/if}}&nbsp;{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}
				</div>
				<div class="wall-item-content conv-list-mode" id="wall-item-content-{{$item.id}}">
					<div class="wall-item-title-end"></div>
					<div class="wall-item-body wall-item-listbody" id="wall-item-body-{{$item.id}}" >
						{{$item.body}}
						{{if $item.tags}}
						<div class="body-tag">
						{{foreach $item.tags as $tag}}
							<span class='tag'>{{$tag}}</span>
						{{/foreach}}
						</div>
						{{/if}}
						{{if $item.has_cats}}
						<div class="categorytags">
							<span>{{$item.txt_cats}} {{foreach $item.categories as $cat}}{{$cat.name}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
						</div>
						{{/if}}
							{{if $item.has_folders}}
						<div class="filesavetags">
							<span>{{$item.txt_folders}} {{foreach $item.folders as $cat}}{{$cat.name}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
						</div>
						{{/if}}
					</div>
				</div>
				<div class="wall-item-tools">
					<div class="wall-item-tools-right btn-group pull-right">
						{{if $item.like}}
						<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$item.id}},'like'); return false">
							<i class="icon-thumbs-up-alt" title="{{$item.like.0}}"></i>
						</button>
						{{/if}}
						{{if $item.dislike}}
						<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$item.id}},'dislike'); return false">
							<i class="icon-thumbs-down-alt" title="{{$item.dislike.0}}"></i>
						</button>
						{{/if}}
						<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="wall-item-menu-{{$item.id}}">
							<i class="icon-caret-down"></i>
						</button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-menu-{{$item.id}}">
							{{if $item.share}}
							<li role="presentation"><a role="menuitem" href="#" onclick="jotShare({{$item.id}}); return false"><i class="icon-retweet" title="{{$item.share.0}}"></i> {{$item.share.0}}</a></li>
							{{/if}}
							{{if $item.plink}}
							<li role="presentation"><a role="menuitem" href="{{$item.plink.href}}" title="{{$item.plink.title}}" ><i class="icon-external-link"></i> {{$item.plink.title}}</a></li>
							{{/if}}
							{{if $item.edpost}}
							<li role="presentation"><a role="menuitem" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"><i class="editpost icon-pencil"></i> {{$item.edpost.1}}</a></li>
							{{/if}}
							{{if $item.tagger}}
							<li role="presentation"><a role="menuitem" href="#"  onclick="itemTag({{$item.id}}); return false;"><i id="tagger-{{$item.id}}" class="icon-tag" title="{{$item.tagger.tagit}}"></i> {{$item.tagger.tagit}}</a></li>
							{{/if}}
							{{if $item.filer}}
							<li role="presentation"><a role="menuitem" href="#" onclick="itemFiler({{$item.id}}); return false;"><i id="filer-{{$item.id}}" class="icon-folder-open" title="{{$item.filer}}"></i> {{$item.filer}}</a></li>
							{{/if}}
							{{if $item.bookmark}}
							<li role="presentation"><a role="menuitem" href="#" onclick="itemBookmark({{$item.id}}); return false;"><i id="bookmarker-{{$item.id}}" class="icon-bookmark" title="{{$item.bookmark}}"></i> {{$item.bookmark}}</a></li>
							{{/if}}
							{{if $item.addtocal}}
							<li role="presentation"><a role="menuitem" href="#" onclick="itemAddToCal({{$item.id}}); return false;"><i id="addtocal-{{$item.id}}" class="icon-calendar" title="{{$item.addtocal}}"></i> {{$item.addtocal}}</a></li>
							{{/if}}
							{{if $item.star}}
							<li role="presentation"><a role="menuitem" href="#" onclick="dostar({{$item.id}}); return false;"><i id="starred-{{$item.id}}" class="icon-star {{$item.star.isstarred}}" title="{{$item.star.toggle}}"></i> {{$item.star.toggle}}</a></li>
							{{/if}}
							{{if $item.item_photo_menu}}
							<li role="presentation" class="divider"></li>
							{{$item.item_photo_menu}}
							{{/if}}
							{{if $item.drop.dropping}}
							<li role="presentation" class="divider"></li>
							<li role="presentation"><a role="menuitem" href="item/drop/{{$item.id}}" onclick="return confirmDelete();" title="{{$item.drop.delete}}" ><i class="icon-remove"></i> {{$item.drop.delete}}</a></li>
							{{/if}}
						</ul>
					</div>
					<div id="like-rotator-{{$item.id}}" class="like-rotator"></div>
					<div class="wall-item-tools-left{{if $item.unseen_comments || $item.like_count || $item.dislike_count}} btn-group{{/if}}">


				<div class="wall-item-list-comments btn-group"><button class="btn btn-default btn-sm" onclick="window.location.href='{{$item.viewthread}}'; return false;">{{$item.comment_count_txt}}{{if $item.unseen_comments}}
<span class="unseen-wall-indicator-{{$item.id}}">, {{$item.list_unseen_txt}}{{/if}}</span></button></div>{{if $item.unseen_comments}}<div class="unseen-wall-indicator-{{$item.id}} btn-group"><button class="btn btn-default btn-sm" title="{{$item.markseen}}" onclick="markItemRead({{$item.id}}); return false;"><i class="icon-check"></i></div>{{/if}}

						{{if $item.like_count}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="wall-item-like-{{$item.id}}">{{$item.like_count}} {{$item.like_button_label}}</button>
							{{if $item.like_list_part}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-like-{{$item.id}}">{{foreach $item.like_list_part as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{else}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-like-{{$item.id}}">{{foreach $item.like_list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{/if}}
						</div>
						{{/if}}
						{{if $item.dislike_count}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-dislike dropdown-toggle" data-toggle="dropdown" id="wall-item-dislike-{{$item.id}}">{{$item.dislike_count}} {{$item.dislike_button_label}}</button>
							{{if $item.dislike_list_part}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-dislike-{{$item.id}}">{{foreach $item.dislike_list_part as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
							{{else}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-dislike-{{$item.id}}">{{foreach $item.dislike_list as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
							{{/if}}
						</div>
						{{/if}}
					</div>
					{{if $item.like_list_part}}
					<div class="modal" id="likeModal-{{$item.id}}">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									<h4 class="modal-title">{{$item.like_modal_title}}</h4>
								</div>
								<div class="modal-body">
									<ul>{{foreach $item.like_list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
								</div>
								<div class="modal-footer clear">
									<button type="button" class="btn btn-default" data-dismiss="modal">{{$item.modal_dismiss}}</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
					{{/if}}
					{{if $item.dislike_list_part}}
					<div class="modal" id="dislikeModal-{{$item.id}}">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									<h4 class="modal-title">{{$item.dislike_modal_title}}</h4>
								</div>
								<div class="modal-body">
									<ul>{{foreach $item.dislike_list as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
								</div>
								<div class="modal-footer clear">
									<button type="button" class="btn btn-default" data-dismiss="modal">{{$item.modal_dismiss}}</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
					{{/if}}
				</div>
				<div class="clear"></div>
			</div>
			<div class="wall-item-wrapper-end"></div>
			<div class="wall-item-outside-wrapper-end {{$item.indent}}" ></div>
		</div>
	</div>
{{if $item.comment_lastcollapsed}}
</div>
{{/if}}
