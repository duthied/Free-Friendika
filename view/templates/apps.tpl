{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h3>{{$title}}</h3>

<ul>
	{{foreach $apps as $ap}}
	<li>{{$ap nofilter}}</li>
	{{/foreach}}
</ul>
