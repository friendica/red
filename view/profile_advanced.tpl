<h2>$title</h2>

<dl>
 <dt>$profile.fullname.0</dt>
 <dd>$profile.fullname.1</dd>
</dl>

{{ if $profile.gender }}
<dl>
 <dt>$profile.gender.0</dt>
 <dd>$profile.gender.1</dd>
</dl>
{{ endif }}

{{ if $profile.birthday }}
<dl>
 <dt>$profile.birthday.0</dt>
 <dd>$profile.birthday.1</dd>
</dl>
{{ endif }}

{{ if $profile.age }}
<dl>
 <dt>$profile.age.0</dt>
 <dd>$profile.age.1</dd>
</dl>
{{ endif }}

{{ if $profile.marital }}
<dl>
 <dt><span class="heart">&hearts;</span>  $profile.marital.0</dt>
 <dd>$profile.marital.1  {{ if $profile.marital.with }}($profile.marital.with){{ endif }}</dd>
</dl>
{{ endif }}

{{ if $profile.sexual }}
<dl>
 <dt>$profile.sexual.0</dt>
 <dd>$profile.sexual.1</dd>
</dl>
{{ endif }}

{{ if $profile.homepage }}
<dl>
 <dt>$profile.homepage.0</dt>
 <dd>$profile.homepage.1</dd>
</dl>
{{ endif }}

{{ if $profile.politic }}
<dl>
 <dt>$profile.politic.0</dt>
 <dd>$profile.politic.1</dd>
</dl>
{{ endif }}

{{ if $profile.religion }}
<dl>
 <dt>$profile.religion.0</dt>
 <dd>$profile.religion.1</dd>
</dl>
{{ endif }}

{{ if $profile.about }}
<dl>
 <dt>$profile.about.0</dt>
 <dd>$profile.about.1</dd>
</dl>
{{ endif }}

{{ if $profile.interest }}
<dl>
 <dt>$profile.interest.0</dt>
 <dd>$profile.interest.1</dd>
</dl>
{{ endif }}


{{ if $profile.contact }}
<dl>
 <dt>$profile.contact.0</dt>
 <dd>$profile.contact.1</dd>
</dl>
{{ endif }}


{{ if $profile.music }}
<dl>
 <dt>$profile.music.0</dt>
 <dd>$profile.music.1</dd>
</dl>
{{ endif }}


{{ if $profile.book }}
<dl>
 <dt>$profile.book.0</dt>
 <dd>$profile.book.1</dd>
</dl>
{{ endif }}


{{ if $profile.tv }}
<dl>
 <dt>$profile.tv.0</dt>
 <dd>$profile.tv.1</dd>
</dl>
{{ endif }}


{{ if $profile.film }}
<dl>
 <dt>$profile.film.0</dt>
 <dd>$profile.film.1</dd>
</dl>
{{ endif }}


{{ if $profile.romance }}
<dl>
 <dt>$profile.romance.0</dt>
 <dd>$profile.romance.1</dd>
</dl>
{{ endif }}


{{ if $profile.work }}
<dl>
 <dt>$profile.work.0</dt>
 <dd>$profile.work.1</dd>
</dl>
{{ endif }}

{{ if $profile.education }}
<dl>
 <dt>$profile.education.0</dt>
 <dd>$profile.education.1</dd>
</dl>
{{ endif }}




