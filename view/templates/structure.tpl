Table {{$name}}
===========
{{$comment}}

{{foreach $fields as $field}}
| {{$field.name}} | {{$field.comment}} | {{$field.type}} | {{$field.null}} | {{$field.primary}} | {{$field.default}} | {{$field.extra}} |    
{{/foreach}}

Return to [database documentation](help/database)
