{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="imagegrid">
	{{foreach $rows as $img}}
	<div class="imagegrid-row">
		{{include file="content/image/single.tpl" image=$img.0}}
		{{if $img.1}}
		{{include file="content/image/single.tpl" image=$img.1}}
		{{/if}}
	</div>
	{{/foreach}}
</div>
