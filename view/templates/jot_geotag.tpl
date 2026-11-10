{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			$('#jot-coord').val(position.coords.latitude + ' ' + position.coords.longitude);
			$('#profile-nolocation-wrapper').show();
		});
	}

