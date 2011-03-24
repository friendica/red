<h1>Ladda upp profilbild</h1>

<form enctype="multipart/form-data" action="profile_photo" method="post">

<div id="profile-photo-upload-wrapper">
<label id="profile-photo-upload-label" for="profile-photo-upload">Ladda upp fil: </label>
<input name="userfile" type="file" id="profile-photo-upload" size="48" />
</div>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="Ladda upp">
</div>

</form>

<div id="profile-photo-link-select-wrapper">
eller <a href='photos/$user'>v&auml;lj bild i ett album</a>
</div>