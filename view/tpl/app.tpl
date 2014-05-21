<div class="app-container">
<a href="{{$app.url}}" {{if $app.hover}}title="{{$app.hover}}"{{/if}}><img src="{{$app.photo}}" width="80" height="80" />
<div class="app-name">{{$app.name}}</div>
</a>
{{if $install || $update || $delete }}
<form action="appman" method="post">
<input type="hidden" name="papp" value="{{$app.papp}}" />
{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-default" title="{{$install}}" ><i class="icon-download-alt" ></i></button>{{/if}}
{{if $delete}}<button type="submit" name="delete" value="{{$delete}}" class="btn btn-default" title="{{$delete}}" ><i class="icon-remove drop-icons"></i></button>{{/if}}
</form>
{{/if}}
</div>

