<?xml version="1.0" encoding="utf-8"?>
<response>
{{ if $response.sorted }}<sorted>$response.sorted</sorted>{{ endif }}
{{ if $response.filtered }}<filtered>$response.filtered</filtered>{{ endif }}
{{ if $response.updatedSince }}<updatedSince>$response.updatedSince</updatedSince>{{ endif }}
{{ if $response.startIndex }}<startIndex>$response.startIndex</startIndex>{{ endif }}
{{ if $response.itemsPerPage }}<itemsPerPage>$response.itemsPerPage</itemsPerPage>{{ endif }}
{{ if $response.totalResults }}<totalResults>$response.totalResults</totalResults>{{ endif }}

{{ for $response.entry as $entry }}
{{ inc poco_entry_xml.tpl }}{{ endinc }}
{{ endfor }}

</response>
