{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $alternate}}
<link href="{{$alternate}}" rel="alternate" type="application/atom+xml">
{{/if}}
{{if $conversation}}
<link href="{{$conversation}}" rel="conversation" type="application/atom+xml">
{{/if}}
