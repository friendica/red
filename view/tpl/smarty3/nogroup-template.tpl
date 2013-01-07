<h1>{{$header}}</h1>

{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
<div id="contact-edit-end"></div>

{{$paginate}}




