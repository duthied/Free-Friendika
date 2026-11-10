{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h2>{{$title}}</h2>
	<form action="webfinger" method="get">
		{{$lookup}} <input type="text" style="width: 250px;" name="addr" value="{{$addr}}" />
		<input type="submit" name="submit" value="{{$submit}}" />
	</form>

	<br /><br />

	{{if $res}}
	<pre>
		{{$res}}
	</pre>
	{{/if}}
</div>
