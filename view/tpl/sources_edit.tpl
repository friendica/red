<h1>{{$title}}</h1>

<div class="descriptive-text">{{$desc}}</div>

<form action="sources" method="post">
<input type="hidden" name="source" value="{{$id}}" />
<input type="hidden" id="id_abook" name="abook" value="{{$abook}}" />
{{include file="field_input.tpl" field=$name}}
{{include file="field_textarea.tpl" field=$words}}

<div class="sources-submit-wrapper" >
<input type="submit" name="submit" class="sources-submit" value="{{$submit}}" />
</div>
</form>
<br />
<br />
<a href="sources/{{$id}}/drop">{{$drop}}</a>





