{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="contact-block">
<h3 class="contact-block-h4">{{$contacts}}</h3>
{{if $micropro}}
		<a class="allcontact-link" href="profile/{{$nickname}}/contacts">{{$viewcontacts}}</a>
		<div class='contact-block-content'>
		{{foreach $micropro as $m}}
			{{$m nofilter}}
		{{/foreach}}
		</div>
{{/if}}
</div>
<div class="clear"></div>
