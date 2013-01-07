
<h1>{{$title}}</h1>
<h2>{{$pass}}</h2>


<p>
{{$info_01}}<br>
{{$info_02}}<br>
{{$info_03}}
</p>

{{if $status}}
<h3 class="error-message">{{$status}}</h3>
{{/if}}

<form id="install-form" action="{{$baseurl}}/setup" method="post">

<input type="hidden" name="phpath" value="{{$phpath}}" />
<input type="hidden" name="pass" value="3" />

{{include file="field_input.tpl" field=$dbhost}}
{{include file="field_input.tpl" field=$dbuser}}
{{include file="field_password.tpl" field=$dbpass}}
{{include file="field_input.tpl" field=$dbdata}}


<input id="install-submit" type="submit" name="submit" value="{{$submit}}" /> 

</form>

