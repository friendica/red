
<h1>Friend/Connection Request</h1>

<p id="dfrn-request-intro">
Please enter your profile address from one of the following supported social networks:<br />
<ul id="dfrn-request-networks">
<li><a href="http://friendika.com" title="Private (secure) network">Friendika</a> <img src="images/lock_icon.gif" alt="Private (secure) network" title="Private (secure) network" /></li>
<li><a href="http://ostatus.org" title="Public (insecure) network" >StatusNet/Federated Social Web</a> <img src="images/unlock_icon.gif" alt="Public (insecure) network" title="Public (insecure) network"/></li>
</ul>
</p>

<form action="dfrn_request/$nickname" method="post" />

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >Your profile address:</label>
	<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="$myaddr" />
	<div id="dfrn-request-url-end"></div>
</div>


<div id="dfrn-request-info-wrapper" >

</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="Submit Request" />
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="Cancel" />
	</div>
</form>
