{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
{{* Create an object with the data which is needed for infinite scroll.
	For the relevant js part look at function loadContent() in main.js. *}}
	var infinite_scroll = {
		"reload_uri": "{{$reload_uri nofilter}}"
	}
</script>
