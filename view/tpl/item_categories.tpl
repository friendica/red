{{if $categories}}
<div class="categorytags">
{{foreach $categories as $cat}}
<span class="item-category"><i class="icon-asterisk cat-icons"></i>&nbsp;{{if $cat.url}}<a href="{{$cat.url}}">{{$cat.term}}</a>{{else}}{{$cat.term}}{{/if}}{{if $cat.writeable}}&nbsp;<a href="{{$cat.removelink}}" class="category-remove-link" title="{{$remove}}"><i class="icon-remove drop-icons"></i></a>{{/if}}</span>
{{/foreach}}
</div>
{{/if}}

