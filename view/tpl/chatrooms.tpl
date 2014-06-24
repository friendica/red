<h2>{{$header}}</h2>

{{if $is_owner}}
<p>
<a href="{{$baseurl}}/chat/{{$nickname}}/new">{{$newroom}}</a>
</p>
{{/if}}

{{$rooms}}

