<entry>
{{ if $entry.id }}<id>$entry.id</id>{{ endif }}
{{ if $entry.displayName }}<displayName>$entry.displayName</displayName>{{ endif }}
{{ if $entry.preferredName }}<preferredName>$entry.preferredName</preferredName>{{ endif }}
{{ if $entry.urls }}<urls><value>$entry.urls.value</value><type>$entry.urls.type</type></urls>{{ endif }}
{{ if $entry.photos }}<photos><value>$entry.photos.value</value><type><$entry.photos.type></type></photos>{{ endif }}
</entry>
