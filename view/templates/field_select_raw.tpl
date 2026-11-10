{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="field select">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select name="{{$field.0}}" id="id_{{$field.0}}" aria-describedby="{{$field.0}}_tip">
			{{$field.4 nofilter}}
		</select>
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
