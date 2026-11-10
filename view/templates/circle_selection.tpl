{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="field custom">
<label for="{{$id}}" id="circle-selection-lbl">{{$label}}</label>
<select name="{{$id}}" id="{{$id}}">
{{foreach $circles as $circle}}
<option value="{{$circle.id}}"{{if $circle.selected}} selected="selected"{{/if}}>{{$circle.name}}</option>
{{/foreach}}
</select>
</div>
