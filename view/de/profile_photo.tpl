<h1>Profilbild Hochladen</h1>

<form enctype="multipart/form-data" action="profile_photo" method="post">

<div id="profile-photo-upload-wrapper">
<label id="profile-photo-upload-label" for="profile-photo-upload">Datei hochladen: </label>
<input name="userfile" type="file" id="profile-photo-upload" size="48" />
</div>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="Upload">
</div>

</form>

<div id="profile-photo-link-select-wrapper">
oder <a href='photos/$user'>wähle ein Bild aus einem Album</a>
</div>
