{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{include file="section_title.tpl"}}
<p>{{$thanks}}</p>

<ul class="credits">
{{foreach $names as $name}}
 <li>{{$name}}</li>
{{/foreach}}
</ul>
