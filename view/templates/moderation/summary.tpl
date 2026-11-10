{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<dl>
		<dt>{{$users.0}}</dt>
		<dd>{{$users.1}}</dd>
	</dl>
	{{foreach $accounts as $p}}
		<dl>
			<dt>{{$p.0}}</dt>
			<dd>{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</dd>
		</dl>
	{{/foreach}}

</div>
