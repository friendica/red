<h1>{{$title}}</h1>

<div id="remove-account-wrapper">

<div id="remove-account-desc">{{$desc}}</div>

<form action="{{$basedir}}/removeme" autocomplete="off" method="post" >
<input type="hidden" name="verify" value="{{$hash}}" />

<div id="remove-account-pass-wrapper">
<label id="remove-account-pass-label" for="remove-account-pass">{{$passwd}}</label>
<input type="password" id="remove-account-pass" name="qxz_password" />
</div>
<div id="remove-account-pass-end"></div>

<input type="submit" name="submit" value="{{$submit}}" />

</form>
</div>

