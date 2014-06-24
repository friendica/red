<users type="array">
	{{foreach $users as $user}}
	{{include file="api_user_xml.tpl"}}
	{{/foreach}}
</users>
