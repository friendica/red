<h1>{{$header}}</h1>
<form id="sellpage-edit" action="connect/{{$address}}" method="post">

{{include file="field_checkbox.tpl" field=$premium}}

<div class="descriptive-text">{{$desc}}</div>

<div class="sellpage-editbody">
<p id="sellpage-bodydesc" >
{{$lbl_about}}
</p>

<textarea rows="10" cols="72" id="sellpage-textinp" name="text" >{{$text}}</textarea>

</div>
<div id="sellpage-editbody-end"></div>


<div class="descriptive-text">{{$lbl2}}</div>
<div class="sellpage-final">{{$desc2}}</div>

<input type="submit" name="submit" value="{{$submit}}" />
</form>
