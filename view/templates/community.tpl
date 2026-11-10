{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h3>{{$header}}</h3>

{{if $show_global_community_hint}}
<p class="hint">{{$global_community_hint}}</p>
{{/if}}

{{$content nofilter}}
