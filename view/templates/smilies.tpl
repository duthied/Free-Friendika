{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="smilies" class="generic-page-wrapper">
	<div class="smiley-sample">
		{{for $i=0 to $count}}
		<dl>
			<dt>{{$smilies.texts[$i] nofilter}}</dt>
			<dd>{{$smilies.icons[$i] nofilter}}</dd>
		</dl>
		{{/for}}
	</div>
</div>
