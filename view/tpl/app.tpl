<div class="app-container">
<a href="{{$app.url}}" {{if $ap.target}}target="{{$ap.target}}" {{/if}}{{if $app.desc}}title="{{$app.desc}}{{if $app.price}} ({{$app.price}}){{/if}}"{{else}}title="{{$app.name}}"{{/if}}><img src="{{$app.photo}}" width="80" height="80" />
<div class="app-name" style="text-align:center;">{{$app.name}}</div>
</a>
{{if $app.type !== 'system'}}
{{if $purchase}}
<a href="{{$app.page}}" class="btn btn-default" title="{{$purchase}}" ><i class="icon-external"></i></a>
{{/if}}
{{if $install || $update || $delete }}
<form action="{{$hosturl}}appman" method="post">
<input type="hidden" name="papp" value="{{$app.papp}}" />
{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-default" title="{{$install}}" ><i class="icon-download-alt" ></i></button>{{/if}}
{{if $edit}}<input type="hidden" name="appid" value="{{$app.guid}}" /><button type="submit" name="edit" value="{{$edit}}" class="btn btn-default" title="{{$edit}}" ><i class="icon-pencil" ></i></button>{{/if}}
{{if $delete}}<button type="submit" name="delete" value="{{$delete}}" class="btn btn-default" title="{{$delete}}" ><i class="icon-trash drop-icons"></i></button>{{/if}}
</form>
{{/if}}
{{/if}}
</div>

