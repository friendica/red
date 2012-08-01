<h3>$header</h3>

<div id="delegate-desc" class="delegate-desc">$desc</div>

{{ if $managers }}
<h3>$head_managers</h3>

{{ for $managers as $x }}

<div class="contact-block-div">
<a class="contact-block-link" href="#" >
<img class="contact-block-img" src="$base/photo/thumb/$x.uid" title="$x.username ($x.nickname)" />
</a>
</div>

{{ endfor }}
<div class="clear"></div>
<hr />
{{ endif }}


<h3>$head_delegates</h3>

{{ if $delegates }}
{{ for $delegates as $x }}

<div class="contact-block-div">
<a class="contact-block-link" href="$base/delegate/remove/$x.uid" >
<img class="contact-block-img" src="$base/photo/thumb/$x.uid" title="$x.username ($x.nickname)" />
</a>
</div>

{{ endfor }}
<div class="clear"></div>
{{ else }}
$none
{{ endif }}
<hr />


<h3>$head_potentials</h3>
{{ if $potentials }}
{{ for $potentials as $x }}

<div class="contact-block-div">
<a class="contact-block-link" href="$base/delegate/add/$x.uid" >
<img class="contact-block-img" src="$base/photo/thumb/$x.uid" title="$x.username ($x.nickname)" />
</a>
</div>

{{ endfor }}
<div class="clear"></div>
{{ else }}
$none
{{ endif }}
<hr />

