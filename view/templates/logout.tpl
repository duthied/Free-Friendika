{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form action="{{$dest_url}}" method="post">
<div class="logout-wrapper">
<input type="hidden" name="auth-params" value="logout" />
<input type="submit" name="submit" id="logout-button" value="{{$logout}}" />
</div>
</form>
