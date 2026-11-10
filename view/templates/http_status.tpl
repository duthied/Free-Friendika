{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<html>
	<head>
		<title>{{$title}}</title>
	</head>
	<body>
		<h1>{{$title}}</h1>
		<p>{{$message}}</p>
	{{if $trace}}
		<pre>{{$trace nofilter}}</pre>
	{{/if}}
	{{if $request_id}}
		<pre>Request: {{$request_id}}</pre>
	{{/if}}
	</body>
</html>
