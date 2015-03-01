<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<button title="{{$permanent}}" class="btn btn-danger btn-xs pull-right" type="submit" formaction="removeme"><i class="icon-trash"></i>&nbsp;{{$removeme}}</button>
		<h2>{{$ptitle}}</h2>
		<div class="clear"></div>
	</div>

	{{$nickname_block}}

	<form action="settings" id="settings-form" method="post" autocomplete="off" >
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}' />



	<div class="panel-group" id="settings" role="tablist" aria-multiselectable="true">
		<div class="panel">
			<div class="section-subtitle-wrapper" role="tab" id="basic-settings">
				<h3>
					<a data-toggle="collapse" data-parent="#settings" href="#basic-settings-collapse" aria-expanded="true" aria-controls="basic-settings-collapse">
						{{$h_basic}}
					</a>
				</h3>
			</div>
			<div id="basic-settings-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="basic-settings">
				<div class="section-content-tools-wrapper">
					{{include file="field_input.tpl" field=$username}}
					{{include file="field_select_grouped.tpl" field=$timezone}}
					{{include file="field_input.tpl" field=$defloc}}
					{{include file="field_checkbox.tpl" field=$allowloc}}
					{{include file="field_checkbox.tpl" field=$adult}}
					<div class="settings-submit-wrapper" >
						<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
					</div>
				</div>
			</div>
		</div>
		<div class="panel">
			<div class="section-subtitle-wrapper" role="tab" id="privacy-settings">
				<h3>
					<a data-toggle="collapse" data-parent="#settings" href="#privacy-settings-collapse" aria-expanded="true" aria-controls="privacy-settings-collapse">
						{{$h_prv}}
					</a>
				</h3>
			</div>
			<div id="privacy-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="privacy-settings">
				<div class="section-content-tools-wrapper">
					{{include file="field_select_grouped.tpl" field=$role}}

					<div id="advanced-perm" style="display:{{if $permissions_set}}none{{else}}block{{/if}};">
						{{include file="field_checkbox.tpl" field=$hide_presence}}
						<button type="button" class="btn btn-default" data-toggle="collapse" data-target="#settings-permissions-wrapper">{{$lbl_p2macro}}</button>

						<div class="collapse well" id="settings-permissions-wrapper">

							{{foreach $permiss_arr as $permit}}
								{{include file="field_select.tpl" field=$permit}}
							{{/foreach}}
							<div class="settings-submit-wrapper" >
								<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
							</div>
						</div>

						<div id="settings-default-perms" class="settings-default-perms" >
							<button class="btn btn-default" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
								{{$aclselect}}
							<div id="settings-default-perms-menu-end"></div>
						</div>
						<div id="settings-default-perms-end"></div>
						{{$group_select}}
						{{$profile_in_dir}}
					</div>
					<div class="settings-common-perms">
						{{$suggestme}}
						{{include file="field_checkbox.tpl" field=$blocktags}}
						{{include file="field_input.tpl" field=$expire}}
					</div>
					<div class="settings-submit-wrapper" >
						<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
					</div>
				</div>
			</div>
		</div>
		<div class="panel">
			<div class="section-subtitle-wrapper" role="tab" id="notification-settings">
				<h3>
					<a data-toggle="collapse" data-parent="#settings" href="#notification-settings-collapse" aria-expanded="true" aria-controls="notification-settings-collapse">
						{{$h_not}}
					</a>
				</h3>
			</div>
			<div id="notification-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="notification-settings">
				<div class="section-content-tools-wrapper">
					<div id="settings-notifications">
						<div id="settings-activity-desc">{{$activity_options}}</div>
						<div class="group">
							{{*not yet implemented *}}
							{{*include file="field_checkbox.tpl" field=$post_joingroup*}}
							{{include file="field_checkbox.tpl" field=$post_newfriend}}
							{{include file="field_checkbox.tpl" field=$post_profilechange}}
						</div>
						<div id="settings-notify-desc">{{$lbl_not}}</div>
						<div class="group">
							{{include file="field_intcheckbox.tpl" field=$notify1}}
							{{include file="field_intcheckbox.tpl" field=$notify2}}
							{{include file="field_intcheckbox.tpl" field=$notify3}}
							{{include file="field_intcheckbox.tpl" field=$notify4}}
							{{include file="field_intcheckbox.tpl" field=$notify5}}
							{{include file="field_intcheckbox.tpl" field=$notify6}}
							{{include file="field_intcheckbox.tpl" field=$notify7}}
							{{include file="field_intcheckbox.tpl" field=$notify8}}
						</div>
						<div id="settings-vnotify-desc">{{$lbl_vnot}}</div>
						<div class="group">
							{{include file="field_intcheckbox.tpl" field=$vnotify1}}
							{{include file="field_intcheckbox.tpl" field=$vnotify2}}
							{{include file="field_intcheckbox.tpl" field=$vnotify3}}
							{{include file="field_intcheckbox.tpl" field=$vnotify4}}
							{{include file="field_intcheckbox.tpl" field=$vnotify5}}
							{{include file="field_intcheckbox.tpl" field=$vnotify6}}
							{{include file="field_intcheckbox.tpl" field=$vnotify10}}
							{{include file="field_intcheckbox.tpl" field=$vnotify7}}
							{{include file="field_intcheckbox.tpl" field=$vnotify8}}
							{{include file="field_intcheckbox.tpl" field=$vnotify9}}
							{{include file="field_intcheckbox.tpl" field=$vnotify11}}
							{{include file="field_intcheckbox.tpl" field=$always_show_in_notices}}

							{{*include file="field_intcheckbox.tpl" field=$vnotify11*}}
						</div>
						{{include file="field_input.tpl" field=$evdays}}
					</div>
					<div class="settings-submit-wrapper" >
						<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
					</div>
				</div>
			</div>
		</div>
		{{if $menus}}
		<div class="panel">
			<div class="section-subtitle-wrapper" role="tab" id="miscellaneous-settings">
				<h3>
					<a data-toggle="collapse" data-parent="#settings" href="#miscellaneous-settings-collapse" aria-expanded="true" aria-controls="miscellaneous-settings-collapse">
						{{$lbl_misc}}
					</a>
				</h3>
			</div>
			<div id="miscellaneous-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="miscellaneous-settings">
				<div class="section-content-wrapper">
					<div class="form-group">
						<label for="channel_menu">{{$menu_desc}}</label>
						<select name="channel_menu" class="form-control">
							{{foreach $menus as $menu }}
							<option value="{{$menu.name}}" {{$menu.selected}} >{{$menu.name}} </option>
							{{/foreach}}
						</select>
					</div>
					<div class="settings-submit-wrapper" >
						<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
					</div>
				</div>
			</div>
		</div>
		{{/if}}
	</div>
</div>
