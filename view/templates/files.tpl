{{foreach $items as $item }}
<p>{{$item.title}}  ({{$item.mime}}) ({{$item.filename}})</p>
{{/foreach}}
{{include "paginate.tpl"}}