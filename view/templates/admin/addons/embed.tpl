{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form method="post" action="{{$action}}">
    <input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	{{$form nofilter}}
</form>
