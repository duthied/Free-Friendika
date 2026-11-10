{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.description}}</p>

	<form action="" method="post">
		<table class="table-striped table-condensed">
{{foreach $threads as $thread}}
			<tr>
				<td>
					<div id="tread-wrapper-{{$thread.id}}" class="tread-wrapper panel toplevel_item">
    {{foreach $thread.items as $item}}
                        {{include file="{{$item.template}}"}}
    {{/foreach}}
					</div>
				</td>
				<td>
					<input type="checkbox" name="uri-ids[]" value="{{$thread.items[0].uriid}}">
				</td>
			</tr>
{{/foreach}}
		</table>
		<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
