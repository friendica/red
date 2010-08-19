<h1>Edit Profile Details</h1>

<div id="profile-edit-links">
<ul>
<li><a href="profile/$profile_id/view" id="profile-edit-view-link" title="View this profile">View this profile</a></li>
<li><a href="profiles/clone/$profile_id" id="profile-edit-clone-link" title="Create a new profile using these settings">Clone this profile</a></li>
<li></li>
<li><a href="profiles/drop/$profile_id" id="profile-edit-drop-link" title="Delete this profile" $disabled >Delete this profile</a></li>

</ul>
</div>

<div id="profile-edit-links-end"></div>

$default

<div id="profile-edit-wrapper" >
<form id="profile-edit-form" name="form1" action="profiles/$profile_id" method="post" >

<div id="profile-edit-profile-name-wrapper" >
<label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >Profile Name: </label>
<input type="text" size="32" name="profile_name" id="profile-edit-profile-name" value="$profile_name" /><div class="required">*</div>
</div>
<div id="profile-edit-profile-name-end"></div>

<div id="profile-edit-name-wrapper" >
<label id="profile-edit-name-label" for="profile-edit-name" >Your Full Name: </label>
<input type="text" size="32" name="name" id="profile-edit-name" value="$name" />
</div>
<div id="profile-edit-name-end"></div>

<div id="profile-edit-gender-wrapper" >
<label id="profile-edit-gender-label" for="gender-select" >Your Gender: </label>
$gender
</div>
<div id="profile-edit-gender-end"></div>

<div id="profile-edit-dob-wrapper" >
<label id="profile-edit-dob-label" for="dob-select" >Birthday (y/m/d): </label>
<div id="profile-edit-dob" >
$dob $age
</div>
<div id="profile-edit-dob-end"></div>

$profile_in_dir

$profile_in_net_dir

$hide_friends

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="profile-edit-address-wrapper" >
<label id="profile-edit-address-label" for="profile-edit-address" >Street Address: </label>
<input type="text" size="32" name="address" id="profile-edit-address" value="$address" />
</div>
<div id="profile-edit-address-end"></div>

<div id="profile-edit-locality-wrapper" >
<label id="profile-edit-locality-label" for="profile-edit-locality" >Locality/City: </label>
<input type="text" size="32" name="locality" id="profile-edit-locality" value="$locality" />
</div>
<div id="profile-edit-locality-end"></div>


<div id="profile-edit-postal-code-wrapper" >
<label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >Postal/Zip Code: </label>
<input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="$postal_code" />
</div>
<div id="profile-edit-postal-code-end"></div>

<div id="profile-edit-country-name-wrapper" >
<label id="profile-edit-country-name-label" for="profile-edit-country-name" >Country: </label>
<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('$region');">
<option selected="selected" >$country_name</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-country-name-end"></div>

<div id="profile-edit-region-wrapper" >
<label id="profile-edit-region-label" for="profile-edit-region" >Region/State: </label>
<select name="region" id="profile-edit-region" onChange="Update_Globals();" >
<option selected="selected" >$region</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-region-end"></div>

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>

<div id="profile-edit-marital-wrapper" >
<label id="profile-edit-marital-label" for="profile-edit-marital" >Marital Status: </label>
$marital
</div>
<div id="profile-edit-marital-end"></div>

<div id="profile-edit-sexual-wrapper" >
<label id="profile-edit-sexual-label" for="sexual-select" >Sexual Preference: </label>
$sexual
</div>
<div id="profile-edit-sexual-end"></div>



<div id="profile-edit-homepage-wrapper" >
<label id="profile-edit-homepage-label" for="profile-edit-homepage" >Homepage URL: </label>
<input type="text" size="32" name="homepage" id="profile-edit-homepage" value="$homepage" />
</div>
<div id="profile-edit-homepage-end"></div>

<div id="profile-edit-politic-wrapper" >
<label id="profile-edit-politic-label" for="profile-edit-politic" >Political Views: </label>
<input type="text" size="32" name="politic" id="profile-edit-politic" value="$politic" />
</div>
<div id="profile-edit-politic-end"></div>

<div id="profile-edit-religion-wrapper" >
<label id="profile-edit-religion-label" for="profile-edit-religion" >Religion: </label>
<input type="text" size="32" name="religion" id="profile-edit-religion" value="$religion" />
</div>
<div id="profile-edit-religion-end"></div>

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>

<div id="about-jot-wrapper" >
<p id="about-jot-desc" >
Tell us about yourself... 
</p>

<textarea rows="10" cols="72" id="profile-jot-text" name="about" >$about</textarea>

</div>
<div id="about-jot-end"></div>
</div>


<div id="interest-jot-wrapper" >
<p id="interest-jot-desc" >
Hobbies/Interests 
</p>

<textarea rows="10" cols="72" id="interest-jot-text" name="interest" >$interest</textarea>

</div>
<div id="interest-jot-end"></div>
</div>


<div id="contact-jot-wrapper" >
<p id="contact-jot-desc" >
Contact information and Social Networks 
</p>

<textarea rows="10" cols="72" id="contact-jot-text" name="contact" >$contact</textarea>

</div>
<div id="contact-jot-end"></div>
</div>


<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="music-jot-wrapper" >
<p id="music-jot-desc" >
Musical interests 
</p>

<textarea rows="10" cols="72" id="music-jot-text" name="music" >$music</textarea>

</div>
<div id="music-jot-end"></div>
</div>

<div id="book-jot-wrapper" >
<p id="book-jot-desc" >
Books, literature 
</p>

<textarea rows="10" cols="72" id="book-jot-text" name="book" >$book</textarea>

</div>
<div id="book-jot-end"></div>
</div>



<div id="tv-jot-wrapper" >
<p id="tv-jot-desc" >
Television 
</p>

<textarea rows="10" cols="72" id="tv-jot-text" name="tv" >$tv</textarea>

</div>
<div id="tv-jot-end"></div>
</div>



<div id="film-jot-wrapper" >
<p id="film-jot-desc" >
Film/dance/culture/entertainment 
</p>

<textarea rows="10" cols="72" id="film-jot-text" name="film" >$film</textarea>

</div>
<div id="film-jot-end"></div>
</div>


<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="romance-jot-wrapper" >
<p id="romance-jot-desc" >
Love/romance 
</p>

<textarea rows="10" cols="72" id="romance-jot-text" name="romance" >$romance</textarea>

</div>
<div id="romance-jot-end"></div>
</div>



<div id="work-jot-wrapper" >
<p id="work-jot-desc" >
Work/employment 
</p>

<textarea rows="10" cols="72" id="work-jot-text" name="work" >$work</textarea>

</div>
<div id="work-jot-end"></div>
</div>



<div id="education-jot-wrapper" >
<p id="education-jot-desc" >
School/education 
</p>

<textarea rows="10" cols="72" id="education-jot-text" name="education" >$education</textarea>

</div>
<div id="education-jot-end"></div>
</div>



<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="Submit" />
</div>
<div class="profile-edit-submit-end"></div>


</form>
</div>
<script type="text/javascript">Fill_Country('$country_name');Fill_States('$region');</script>