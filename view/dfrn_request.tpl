
<h1>Friend/Connection Request</h1>

<p id="dfrn-request-intro">
You may request a connection with this member if you have a valid profile address<br />
on one of the following social networks:<br />
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

<p id="dfrn-request-options">
Please answer the following:
</p>

<div id="dfrn-request-info-wrapper" >

<p id="doiknowyou">
Does $name know you?
</p>

		<div id="dfrn-request-know-yes-wrapper">
		<label id="dfrn-request-knowyou-yes-label" for="dfrn-request-knowyouyes">Yes</label>
		<input type="radio" name="knowyou" id="knowyouyes" value="1" />

		<div id="dfrn-request-knowyou-break" ></div>	
		</div>
		<div id="dfrn-request-know-no-wrapper">
		<label id="dfrn-request-knowyou-no-label" for="dfrn-request-knowyouno">No</label>
		<input type="radio" name="knowyou" id="knowyouno" value="0" checked="checked" />

		<div id="dfrn-request-knowyou-end"></div>
		</div>


<p id="dfrn-request-message-desc">
Add a personal note:
</p>
	<div id="dfrn-request-message-wrapper">
	<textarea name="dfrn-request-message" rows="4" cols="64" ></textarea>
	</div>


</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="Submit Request" />
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="Cancel" />
	</div>
</form>
