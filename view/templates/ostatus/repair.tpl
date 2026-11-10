{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h2>{{$l10n.title}}</h2>
{{if $total}}
	{{if $contact}}
		<div class="alert alert-info">
            {{$counter}} / {{$total}} : {{$contact.url}}
		</div>
	{{else}}
		<div class="alert alert-success">
			{{$l10n.done}}
		</div>
	{{/if}}
{{else}}
	<div class="alert alert-warning">
		{{$l10n.nocontacts}}
	</div>
{{/if}}
</div>
