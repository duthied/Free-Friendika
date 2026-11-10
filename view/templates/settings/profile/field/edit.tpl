{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<fieldset data-id="{{$profile_field.id}}">
	<legend>&#8801; {{$profile_field.legend}}</legend>

	<input type="hidden" name="profile_field_order[]" value="{{$profile_field.id}}">

	{{include file="field_input.tpl" field=$profile_field.fields.label}}

	{{include file="field_textarea.tpl" field=$profile_field.fields.value}}
	<details>
		<summary>Permissions</summary>
		{{$profile_field.fields.acl nofilter}}
	</details>
</fieldset>
