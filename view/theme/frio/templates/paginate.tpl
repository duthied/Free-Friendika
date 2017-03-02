{{if $pager}}
<div class="{{$pager.class}}">
	{{if $pager.first}}<li class="pager_first {{$pager.first.class}}"><a href="{{$pager.first.url}}">{{$pager.first.text}}</a></li>{{/if}}

	{{if $pager.prev}}<li class="pager_prev {{$pager.prev.class}}"><a href="{{$pager.prev.url}}">{{$pager.prev.text}}</a></li>{{/if}}

	{{foreach $pager.pages as $p}}<li class="pager_{{$p.class}}"><a href="{{$p.url}}">{{$p.text}}</a></li>{{/foreach}}

	{{if $pager.next}}<li class="pager_next {{$pager.next.class}}"><a href="{{$pager.next.url}}">{{$pager.next.text}}</a></li>{{/if}}

	{{if $pager.last}}&nbsp;<li class="pager_last {{$pager.last.class}}"><a href="{{$pager.last.url}}">{{$pager.last.text}}</a></li>{{/if}}
</div>
{{/if}}
