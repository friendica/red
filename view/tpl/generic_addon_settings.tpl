<div class="panel">
	<div class="section-subtitle-wrapper" role="tab" id="{{$addon.0}}-settings">
		<h3>
			<a title="{{$addon.2}}" data-toggle="collapse" data-parent="#settings" href="#{{$addon.0}}-settings-content" aria-controls="{{$addon.0}}-settings-content">
				{{$addon.1}}
			</a>
		</h3>
	</div>
	<div id="{{$addon.0}}-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="{{$addon.0}}-settings">
		<div class="section-content-tools-wrapper">
			{{$content}}
			{{if $addon.0}}
			<div class="settings-submit-wrapper" >
				<button id="{{$addon.0}}-submit" type="submit" name="{{$addon.0}}-submit" class="btn btn-primary" value="{{$addon.3}}">{{$addon.3}}</button>
			</div>
			{{/if}}
		</div>
	</div>
</div>
