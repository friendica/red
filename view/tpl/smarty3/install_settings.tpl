
<h1>{{$title}}</h1>
<h2>{{$pass}}</h2>


{{if $status}}
<h3 class="error-message">{{$status}}</h3>
{{/if}}

<form id="install-form" action="{{$baseurl}}/setup" method="post">

<input type="hidden" name="phpath" value="{{$phpath}}" />
<input type="hidden" name="dbhost" value="{{$dbhost}}" />
<input type="hidden" name="dbuser" value="{{$dbuser}}" />
<input type="hidden" name="dbpass" value="{{$dbpass}}" />
<input type="hidden" name="dbdata" value="{{$dbdata}}" />
<input type="hidden" name="pass" value="4" />

{{include file="field_input.tpl" field=$adminmail}}
{{include file="field_input.tpl" field=$siteurl}}

{{$timezone}}

<input id="install-submit" type="submit" name="submit" value="{{$submit}}" /> 

</form>

