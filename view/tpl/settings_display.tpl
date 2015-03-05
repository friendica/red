<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$ptitle}}</h2>
	</div>
	<form action="settings/display" id="settings-form" method="post" autocomplete="off" >
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		<div class="panel-group" id="settings" role="tablist" aria-multiselectable="true">
			{{if $theme || $mobile_theme}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="theme-settings-title">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#theme-settings-content" aria-expanded="true" aria-controls="theme-settings-content">
							Theme Settings
						</a>
					</h3>
				</div>
				<div id="theme-settings-content" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="theme-settings">
					<div class="section-content-tools-wrapper">
						{{if $theme}}
							{{include file="field_themeselect.tpl" field=$theme}}
						{{/if}}
						{{if $mobile_theme}}
							{{include file="field_themeselect.tpl" field=$mobile_theme}}
						{{/if}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="custom-settings-title">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#custom-settings-content" aria-expanded="true" aria-controls="custom-settings-content">
							Custom Theme Settings
						</a>
					</h3>
				</div>
				<div id="custom-settings-content" class="panel-collapse collapse{{if !$theme && !$mobile_theme}} in{{/if}}" role="tabpanel" aria-labelledby="custom-settings">
					<div class="section-content-tools-wrapper">
						{{if $theme_config}}
							{{$theme_config}}
						{{/if}}
					</div>
				</div>
			</div>
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="content-settings-title">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#content-settings-content" aria-expanded="true" aria-controls="content-settings-content">
							Content Settings
						</a>
					</h3>
				</div>
				<div id="content-settings-content" class="panel-collapse collapse{{if !$theme && !$mobile_theme && !$theme_config}} in{{/if}}" role="tabpanel" aria-labelledby="content-settings">
					<div class="section-content-wrapper">
						{{include file="field_input.tpl" field=$ajaxint}}
						{{include file="field_input.tpl" field=$itemspage}}
						{{include file="field_input.tpl" field=$channel_divmore_height}}
						{{include file="field_input.tpl" field=$network_divmore_height}}
						{{include file="field_checkbox.tpl" field=$nosmile}}
						{{include file="field_checkbox.tpl" field=$title_tosource}}
						{{include file="field_checkbox.tpl" field=$channel_list_mode}}
						{{include file="field_checkbox.tpl" field=$network_list_mode}}
						{{include file="field_checkbox.tpl" field=$user_scalable}}
						{{if $expert}}
						<div class="form-group">
							<a class="btn btn-default "href="pdledit">{{$layout_editor}}</a>
						</div>
						{{/if}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
