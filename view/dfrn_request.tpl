
<h1>Personal Introduction</h1>

<p id="dfrn-request-intro">
You may introduce yourself to this member if you have a valid profile locator<br />
on the <a href="http://dfrn.org">Distributed Friends and Relations Network (DFRN)</a>.
</p>

<form action="dfrn_request/$nickname" method="post" />

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >Your profile location:</label>
	<input type="text" name="dfrn_url" id="dfrn-url" size="32" />
	<div id="dfrn-request-url-end"></div>
</div>

<p id="dfrn-request-options">
Please answer the following:
</p>

<div id="dfrn-request-info-wrapper" >

<p id="doiknowyou">
Do I know you?
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
