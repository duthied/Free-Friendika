{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<script>
	/* TODO: Consider making a reusable version of this in e.g. main.js instead */
	function showHidePagesList() {
		if ($("li[id^='pages-widget-entry-extended-']").is(':visible')) {
			$("li[id^='pages-widget-entry-extended-']").hide();
			$("li#pages-widget-collapse").html('{{$showmore}}');

		} else {
			$("li[id^='pages-widget-entry-extended-']").show();
			$("li#pages-widget-collapse").html('{{$showless}}');
		}
	}
</script>
<nav id="pages-sidebar" class="widget">
	<button class="widget-btn fakelink" onclick="openCloseWidget('pages-sidebar');" aria-expanded="false">
		<h3>
			<i class="ri ri-file-copy-line" aria-hidden="true"></i>
			{{$title}}
		</h3>
	</button>
	<a class="pull-right widget-action widget-action-top faded-icon" id="sidebar-new-pages"
		href="{{$new_page}}" data-toggle="tooltip" title="{{$create_new_page}}">
		<i class="ri ri-add-line" aria-hidden="true"></i>
	</a>
	{{if $addon_pages_directory_enabled}}
		<a class="pull-right widget-action widget-action-top faded-icon" id="sidebar-pages-directory"
			href="/pagesdirectory" data-toggle="tooltip" title="{{$visit_pagesdirectory}}">
			<i class="ri ri-search-line" aria-hidden="true"></i>
		</a>
	{{/if}}

	<div id="pages-list-sidebar" class="sidebar-widget-list">
		{{* The list of available pages *}}
		<ul id="pages-list-sidebar-ul">
			{{foreach $pages as $page}}
				{{if $page.id <= $visible_pages}}
					<li class="pages-widget-entry pages-{{$page.cid}}" id="pages-widget-entry-{{$page.id}}">
						<span class="notify badge pull-right"></span>
						<a href="{{$page.external_url}}" title="{{$page.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
							<img src="{{$page.micro}}" alt="{{$page.link_desc}}" />
						</a>
						<a class="pages-widget-link" id="pages-widget-link-{{$page.id}}" href="{{$page.url}}">{{$page.name}}</a>
					</li>
				{{/if}}

				{{if $page.id > $visible_pages}}
					<li class="pages-widget-entry pages-{{$page.cid}}" id="pages-widget-entry-extended-{{$page.id}}" style="display: none;">
						<span class="notify badge pull-right"></span>
						<a href="{{$page.external_url}}" title="{{$page.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
							<img src="{{$page.micro}}" alt="{{$page.link_desc}}" />
						</a>
						<a class="pages-widget-link" id="pages-widget-link-{{$page.id}}" href="{{$page.url}}">{{$page.name}}</a>
					</li>
				{{/if}}
			{{/foreach}}

			{{if $total > $visible_pages}}
				<li onclick="showHidePagesList(); return false;" id="pages-widget-collapse" class="pages-widget-link widget-show-more fakelink tool">{{$showmore}}</li>
			{{/if}}
		</ul>
	</div>
</nav>
<script>
	initWidget('pages-sidebar');
</script>
