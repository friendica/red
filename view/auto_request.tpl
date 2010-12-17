
<h1>$header</h1>

<p id="dfrn-request-intro">
$page_desc<br />
<ul id="dfrn-request-networks">
<li><a href="http://friendika.com" title="$private_net">$friendika</a> <img src="images/lock_icon.gif" alt="$private_net" title="$private_net" /></li>
<li><a href="http://ostatus.org" title="$public_net" >$statusnet</a> <img src="images/unlock_icon.gif" alt="$public_net" title="$public_net"/></li>
</ul>
</p>

<form action="dfrn_request/$nickname" method="post" />

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >$your_address</label>
	<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="$myaddr" />
	<div id="dfrn-request-url-end"></div>
</div>


<div id="dfrn-request-info-wrapper" >

</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="$submit" />
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="$cancel" />
	</div>
</form>
