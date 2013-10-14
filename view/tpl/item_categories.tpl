{{if $categories}}
<div class="categorytags">
{{foreach $categories as $cat}}
<span class="item-category"><i class="icon-asterisk cat-icons"></i>&nbsp;{{$cat.term}}{{if $cat.writeable}}<a href="{{$cat.removelink}}" class="category-remove-link" title="{{$remove}}"><i class="icon-remove drop-icons"></i></a>{{/if}}</span>
{{/foreach}}
</div>
{{/if}}

