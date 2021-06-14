Table {{$name}}
===========

{{$comment}}

Fields
------

{{foreach $fields as $field}}
| {{$field.name}} | {{$field.comment}} | {{$field.type}} | {{$field.null}} | {{$field.primary}} | {{$field.default}} | {{$field.extra}} |
{{/foreach}}

Indexes
------------

| Name | Fields |
|------|--------|
{{foreach $indexes as $index}}
| {{$index.name}} | {{$index.fields}} |
{{/foreach}}

{{if $foreign}}
Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
{{foreach $foreign as $key}}
| {{$key.field}} | [{{$key.targettable}}](help/database/db_{{$key.targettable}}) | {{$key.targetfield}} |
{{/foreach}}
{{/if}}

Return to [database documentation](help/database)
