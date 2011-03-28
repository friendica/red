<h1>Téléverser une photo de profil</h1>

<form enctype="multipart/form-data" action="profile_photo" method="post">

<div id="profile-photo-upload-wrapper">
<label id="profile-photo-upload-label" for="profile-photo-upload">Fichier à téléverser: </label>
<input name="userfile" type="file" id="profile-photo-upload" size="48" />
</div>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="Envoyer">
</div>

</form>

<div id="profile-photo-link-select-wrapper">
ou <a href='photos/$user'>choisissez une photo dans vos albums</a>
</div>
