Table {{$name nofilter}}
===========

{{$comment nofilter}}

Fields
------

{{foreach $fields as $field}}
| {{$field.name nofilter}} | {{$field.comment nofilter}} | {{$field.type nofilter}} | {{$field.null nofilter}} | {{$field.primary nofilter}} | {{$field.default nofilter}} | {{$field.extra nofilter}} |
{{/foreach}}

Indexes
------------

{{foreach $indexes as $index}}
| {{$index.name nofilter}} | {{$index.fields nofilter}} |
{{/foreach}}

{{if $foreign}}
Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
{{foreach $foreign as $key}}
| {{$key.field nofilter}} | [{{$key.targettable nofilter}}](help/database/db_{{$key.targettable nofilter}}) | {{$key.targetfield nofilter}} |
{{/foreach}}
{{/if}}

Return to [database documentation](help/database)
