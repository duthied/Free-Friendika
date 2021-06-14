Table {{$name}}
===========
{{$comment}}

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
{{foreach $fields as $field}}
| {{$field.name}} | {{$field.comment}} | {{$field.type}} | {{$field.null}} | {{$field.primary}} | {{$field.default}} | {{$field.extra}} |    
{{/foreach}}

Return to [database documentation](help/database)
