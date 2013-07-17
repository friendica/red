<h3>{{$title}}</h3>

{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}

<div id="view-contact-end"></div>

{{$paginate}}
