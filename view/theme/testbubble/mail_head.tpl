<h3>$messages</h3>

<ul class="tabs-wrapper">
<li><a href="message" class="tabs button {{if $activetab==inbox}}active{{endif}}">$inbox</a></li>
<li><a href="message/sent" class="tabs button {{if $activetab==sent}}active{{endif}}">$outbox</a></li>
<li><a href="message/new" class="tabs button {{if $activetab==new}}active{{endif}}">$new</a></li>
</ul>
