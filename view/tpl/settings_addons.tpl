<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<form action="settings/featured" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		<div class="panel-group" id="settings" role="tablist">
			{{if $diaspora_enabled}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="dspr-settings">
					<h3>
						<a title="{{$dsprdesc}}" data-toggle="collapse" data-parent="#settings" href="#dspr-settings-content" aria-controls="dspr-settings-content">
							{{$dsprtitle}}
						</a>
					</h3>
				</div>
				<div id="dspr-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="dspr-settings">
					<div class="section-content-tools-wrapper">

						{{include file="field_checkbox.tpl" field=$pubcomments}}
						{{include file="field_checkbox.tpl" field=$hijacking}}

						<div class="settings-submit-wrapper" >
							<button type="submit" name="dspr-submit" class="btn btn-primary" value="{{$dsprsubmit}}">{{$dsprsubmit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}
			{{$settings_addons}}
		</div>
	</form>
</div>
