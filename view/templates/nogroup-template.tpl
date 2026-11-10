{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h1>{{$header}}</h1>

{{foreach $contacts as $contact}}
	{{include file="contact/entry.tpl"}}
{{/foreach}}
<div id="contact-edit-end"></div>

{{$paginate nofilter}}
