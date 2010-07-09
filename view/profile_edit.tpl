<h1>Edit Profile Details</h1>

$default

<div id="profile-edit-wrapper" >
<form id="profile-edit-form" name="form1" action="profiles/$profile_id" method="post" >

<div id="profile-edit-profile-name-wrapper" >
<label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >Profile Name: </label>
<input type="text size="32" name="profile_name" id="profile-edit-profile-name" value="$profile_name" /><div class="required">*</div>
</div>
<div id="profile-edit-profile-name-end"></div>

<div id="profile-edit-name-wrapper" >
<label id="profile-edit-name-label" for="profile-edit-name" >Your Full Name: </label>
<input type="text size="32" name="name" id="profile-edit-name" value="$name" />
</div>
<div id="profile-edit-name-end"></div>

<div id="profile-edit-gender-wrapper" >
<label id="profile-edit-gender-label" for="gender-select" >Your Gender: </label>
$gender
</div>
<div id="profile-edit-gender-end"></div>


<div id="profile-edit-address-wrapper" >
<label id="profile-edit-address-label" for="profile-edit-address" >Street Address: </label>
<input type="text size="32" name="address" id="profile-edit-address" value="$address" />
</div>
<div id="profile-edit-address-end"></div>

<div id="profile-edit-locality-wrapper" >
<label id="profile-edit-locality-label" for="profile-edit-locality" >Locality/City: </label>
<input type="text size="32" name="locality" id="profile-edit-locality" value="$locality" />
</div>
<div id="profile-edit-locality-end"></div>


<div id="profile-edit-postal-code-wrapper" >
<label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >Postal/Zip Code: </label>
<input type="text size="32" name="postal_code" id="profile-edit-postal-code" value="$postal_code" />
</div>
<div id="profile-edit-postal-code-end"></div>

<div id="profile-edit-country-name-wrapper" >
<input type="hidden" name="txtSelectedCountry" value="" >

<label id="profile-edit-country-name-label" for="profile-edit-country-name" >Country: </label>
<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('$region');">
<option selected="selected" >$country_name</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-country-name-end"></div>

<div id="profile-edit-region-wrapper" >
<input type="hidden" name="txtSelectedState" value="" >
<label id="profile-edit-region-label" for="profile-edit-region" >Region/State: </label>
<select name="region" id="profile-edit-region" onChange="Update_Globals();" >
<option selected="selected" >$region</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-region-end"></div>


<div id="profile-edit-marital-wrapper" >
<label id="profile-edit-marital-label" for="profile-edit-marital" >Marital Status: </label>
$marital
</div>
<div id="profile-edit-marital-end"></div>

<div id="profile-edit-homepage-wrapper" >
<label id="profile-edit-homepage-label" for="profile-edit-homepage" >Homepage URL: </label>
<input type="text size="32" name="homepage" id="profile-edit-homepage" value="$homepage" />
</div>
<div id="profile-edit-homepage-end"></div>

$profile_in_dir

<div id="about-jot-wrapper" >
<p id="about-jot-desc" >
Tell us about yourself. 
</p>

<textarea rows="13" cols="72" id="profile-jot-text" name="about" >$about</textarea>

</div>
<div id="about-jot-end"></div>
</div>


<div id="profile-edit-submit-wrapper" >
<input type="submit" name="submit" id="profile-edit-submit-button" value="Submit" />
</div>
<div id="profile-edit-submit-end"></div>


</form>
</div>
<script type="text/javascript">Fill_Country('$country_name');Fill_States('$region');</script>