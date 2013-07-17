<div id="side-bar-photos-albums" class="widget">
<h3><a href="{{$baseurl}}/photos/{{$nick}}" title="{{$title}}" >{{$title}}</a></h3>
{{if $albums}}
<ul>
{{foreach $albums as $al}}
<li><a href="{{$baseurl}}/photos/{{$nick}}/album/{{$al.bin2hex}}">{{$al.text}}</a></li>
{{/foreach}}
</ul>
{{/if}}
{{if $upload}}
<div id="photo-albums-upload-link"><a href="{{$baseurl}}/photos/{{$nick}}/upload" title="{{$upload}}">{{$upload}}</a></div>
{{/if}}
</div>
