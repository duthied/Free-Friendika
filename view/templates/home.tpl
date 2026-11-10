{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* custom content from hook will replace everything. *}}
{{if $content != '' }}
	{{$content nofilter}}
{{else}}

	{{if $customhome != false }}
		{{include file="$customhome"}}
	{{else}}
		<h1>{{$defaultheader nofilter}}</h1>
	{{/if}}

	{{$login nofilter}}
{{/if}}
