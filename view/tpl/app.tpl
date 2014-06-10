<div class="app-container">
<a href="{{if $app.alt_url}}{{$app.alt_url}}{{else}}{{$app.url}}{{/if}}" {{if $app.desc}}title="{{$app.desc}}{{if $app.price}} ({{$app.price}}){{/if}}"{{/if}}><img src="{{$app.photo}}" width="80" height="80" />
<div class="app-name">{{$app.name}}</div>
</a>
{{if $purchase}}
<a href="{{$app.page}}" class="btn btn-default" title="{{$purchase}}" ><i class="icon-external"></i></a>
{{/if}}
{{if $install || $update || $delete }}
<form action="{{$hosturl}}appman" method="post">
<input type="hidden" name="papp" value="{{$app.papp}}" />
{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-default" title="{{$install}}" ><i class="icon-download-alt" ></i></button>{{/if}}
{{if $edit}}<input type="hidden" name="appid" value="{{$app.guid}}" /><button type="submit" name="edit" value="{{$edit}}" class="btn btn-default" title="{{$edit}}" ><i class="icon-pencil" ></i></button>{{/if}}
{{if $delete}}<button type="submit" name="delete" value="{{$delete}}" class="btn btn-default" title="{{$delete}}" ><i class="icon-remove drop-icons"></i></button>{{/if}}
</form>
{{/if}}
</div>

