{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form action="invite" method="post" id="invite-form">

	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	<div id="invite-wrapper">

		<h3>{{$title}}</h3>

		{{include file="field_textarea.tpl" field=$recipients}}
		{{include file="field_textarea.tpl" field=$message}}

		<div id="invite-submit-wrapper">
			<input type="submit" name="submit" value="{{$submit}}" />
		</div>

	</div>
</form>
