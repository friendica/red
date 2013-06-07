<div class="pager">
{{if $has_less}}<a href="{{$url}}&page={{$prevpage}}" class="pager-prev">{{$less}}</a>{{/if}}
{{if $has_more}}{{if $has_less}}&nbsp;|&nbsp;{{/if}}<a href="{{$url}}&page={{$nextpage}}" class="pager-next">{{$more}}</a>{{/if}}
</div>
