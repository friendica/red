<entry>
{{if $entry.id}}<id>{{$entry.id}}</id>{{/if}}
{{if $entry.displayName}}<displayName>{{$entry.displayName}}</displayName>{{/if}}
{{if $entry.preferredUsername}}<preferredUsername>{{$entry.preferredUsername}}</preferredUsername>{{/if}}
{{if $entry.urls}}{{foreach $entry.urls as $url}}<urls><value>{{$url.value}}</value><type>{{$url.type}}</type></urls>{{/foreach}}{{/if}}
{{if $entry.photos}}{{foreach $entry.photos as $photo}}<photos><value>{{$photo.value}}</value><type>{{$photo.type}}</type></photos>{{/foreach}}{{/if}}
</entry>
