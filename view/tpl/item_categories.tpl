{{if $categories}}
<div class="categorytags">
{{foreach $categories as $cat}}
<span class="item-category"><i class="icon-asterisk cat-icons"></i>&nbsp;{{if $cat.url}}<a href="{{$cat.url}}">{{$cat.term}}</a>{{else}}{{$cat.term}}{{/if}}</span>
{{/foreach}}
</div>
{{/if}}

