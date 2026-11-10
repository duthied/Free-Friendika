{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


<script>

	$(document).keydown(function(event) {

		if("{{$prevlink}}" != '') { if(event.ctrlKey && event.keyCode == 37) { event.preventDefault(); window.location.href = "{{$prevlink}}"; }}
		if("{{$nextlink}}" != '') { if(event.ctrlKey && event.keyCode == 39) { event.preventDefault(); window.location.href = "{{$nextlink}}"; }}

	});

</script>
