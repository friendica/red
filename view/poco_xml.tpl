<?xml version="1.0" encoding="utf-8"?>
<response>

{{ if $response.sorted }}<sorted>$response.sorted</sorted>{{ endif }}
{{ if $response.filtered }}<filtered>$response.filtered</filtered>{{ endif }}
{{ if $response.updatedSince }}<updatedSince>$response.updatedSince</updatedSince>{{ endif }}
{{ if $response.startIndex }}<startIndex>$response.startIndex</startIndex>{{ endif }}
{{ if $response.itemsPerPage }}<itemsPerPage>$response.itemsPerPage</itemsPerPage>{{ endif }}
{{ if $response.totalResults }}<totalResults>$response.totalResults</totalResults>{{ endif }}

{{ for $response.entry as $ent }}
<entry>
{{ if $ent.id }}<id>$ent.id</id>{{ endif }}
{{ if $ent.displayName }}<displayName>$ent.displayName</displayName>{{ endif }}
{{ if $ent.preferredName }}<preferredName>$ent.preferredName</preferredName>{{ endif }}
{{ if $ent.urls }}<urls><value>$ent.urls.value</value><type>$ent.urls.type</type></urls>{{ endif }}
{{ if $ent.photos }}<photos><value>$ent.photos.value</value><type><$ent.photos.type></type></photos>{{ endif }}
</entry>
{{ endfor }}

</response>
