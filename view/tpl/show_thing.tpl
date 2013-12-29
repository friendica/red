<h2>{{$header}}</h2>
{{if $thing}}
<div class="thing-show">
{{if $thing.imgurl}}<img src="{{$thing.imgurl}}" width="175" height="175" alt="{{$thing.term}}" />{{/if}}
<a href="{{$thing.url}}" >{{$thing.term}}</a>
</div>
{{/if}}

