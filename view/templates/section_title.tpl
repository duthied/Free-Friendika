{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $title}}
<div class="section-title-wrapper{{if isset($pullright)}} pull-left{{/if}}">
	<h2>{{$title}}</h2>
	{{if ! isset($pullright)}}
	<div class="clear"></div>
	{{/if}}
</div>
{{/if}}
