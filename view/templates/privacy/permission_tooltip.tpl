{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{$l10n.visible_to}}<br>
{{if !$aclReceivers->isEmpty()}}
	{{foreach from=$aclReceivers->allowCircles item=circle name=allowCircles}}
		<b>{{$circle}}</b>
		{{if !$smarty.foreach.allowCircles.last}}, {{/if}}
	{{/foreach}}
	{{if $aclReceivers->allowContacts && $aclReceivers->allowCircles}}, {{/if}}
	{{foreach from=$aclReceivers->allowContacts item=contact name=allowContacts}}
		{{$contact}}
		{{if !$smarty.foreach.allowContacts.last}}, {{/if}}
	{{/foreach}}
	{{if $aclReceivers->denyCircles && ($aclReceivers->allowContacts || $aclReceivers->allowCircles)}}, {{/if}}
	{{foreach from=$aclReceivers->denyCircles item=circle name=denyCircles}}
		<b><s>{{$circle}}</s></b>
		{{if !$smarty.foreach.denyCircles.last}}, {{/if}}
	{{/foreach}}
	{{if $aclReceivers->denyContacts && ($aclReceivers->denyCircles || $aclReceivers->allowContacts || $aclReceivers->allowCircles)}}, {{/if}}
	{{foreach from=$aclReceivers->denyContacts item=contact name=denyContacts}}
		<s>{{$contact}}</s>
		{{if !$smarty.foreach.denyContacts.last}}, {{/if}}
	{{/foreach}}
{{elseif !$addressedReceivers->isEmpty()}}
	{{if $addressedReceivers->to}}
		<b>{{$l10n.to}}</b>
		{{', '|join:$addressedReceivers->to}}
		<br>
	{{/if}}
	{{if $addressedReceivers->cc}}
		<b>{{$l10n.cc}}</b>
		{{', '|join:$addressedReceivers->cc}}
		<br>
	{{/if}}
	{{if $addressedReceivers->bcc}}
		<b>{{$l10n.bcc}}</b>
		{{', '|join:$addressedReceivers->bcc}}
		<br>
	{{/if}}
	{{if $addressedReceivers->audience}}
		<b>{{$l10n.audience}}</b>
		{{', '|join:$addressedReceivers->audience}}
		<br>
	{{/if}}
	{{if $addressedReceivers->attributed}}
		<b>{{$l10n.attributed}}</b>
		{{', '|join:$addressedReceivers->attributed}}
		<br>
	{{/if}}
{{else}}
	{{$privacy}}
{{/if}}
