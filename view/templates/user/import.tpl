{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<form action="user/import" method="post" id="uimport-form" enctype="multipart/form-data">
	<h2>{{$import.title}}</h2>
	<p>{{$import.intro}}</p>
	<p>{{$import.instruct}}</p>
	<p><b>{{$import.warn}}</b></p>

	{{include file="field_custom.tpl" field=$import.field}}

	<div id="register-submit-wrapper">
		<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary">{{$regbutt}}</button>
	</div>
	<div id="register-submit-end"></div>
</form>
