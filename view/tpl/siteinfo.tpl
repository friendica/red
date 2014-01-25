<h3>{{$title}}</h3>
<p></p>
<p>{{$description}}</p>
{{if $version}}
<p>{{$version}}{{if $commit}}+{{$commit}}{{/if}}</p>
{{/if}}
<p>{{$web_location}}</p>
<p>{{$visit}}</p>
<p>{{$bug_text}} <a href="{{$bug_link_url}}">{{$bug_link_text}}</a></p>
<p>{{$adminlabel}}</p>
<p>{{$admininfo}}</p>
<p>{{$contact}}</p>
<p>{{$plugins_text}}</p>
{{if $plugins_list}}
   <div style="margin-left: 25px; margin-right: 25px;">{{$plugins_list}}</div>
{{/if}}
