<div class="clear"></div>
<div class="body-attach">
{{foreach $attaches as $a}} 
<a href="{{$a.url}}" title="{{$a.title}}" class="attachlink" ><i class="icon-paper-clip attach-icons attach-clip"></i><i class="{{$a.icon}} attach-icons"></i></a>
{{/foreach}}
</div>
