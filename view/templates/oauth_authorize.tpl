{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h2>{{$title}}</h2>

<div class="oauthapp">
{{if $app.icon}}<img src="{{$app.icon}}" alt="">{{/if}}
	<h3>{{$app.name}}</h3>
</div>
<p>{{$authorize}}</p>
<form method="POST">
<div class="settings-submit-wrapper"><input class="settings-submit" type="submit" name="oauth_yes" value="{{$yes}}" /></div>
</form>
