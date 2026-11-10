{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1><img width="32" height="32" src="{{$baseurl}}/images/friendica.svg"> {{$title}}</h1>
<h2>{{$pass}}</h2>

{{foreach $checks as $check}}
<img src="{{$baseurl}}/view/install/red.png" alt="{{$requirement_not_satisfied}}">
{{$check.title nofilter}}
<textarea rows="24" cols="80">{{$check.help nofilter}}</textarea>
{{/foreach}}

{{$text nofilter}}
