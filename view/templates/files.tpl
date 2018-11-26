{{foreach $items as $item }}
<p>{{$item.title|escape}}  ({{$item.mime|escape}}) ({{$item.filename|escape}})</p>
{{/foreach}}
{{include "paginate.tpl"}}
