<span id="group-sidebar-inflated" class="widget fakelink" onclick="openCloseWidget('group-sidebar', 'group-sidebar-inflated');">
	<h3>{{$title}}</h3>
</span>
<div class="widget" id="group-sidebar">
	<span class="fakelink" onclick="openCloseWidget('group-sidebar', 'group-sidebar-inflated');">
		<h3>{{$title}}</h3>
	</span>

	<div id="sidebar-group-list">
		<ul role="menu" id="sidebar-group-ul">
			{{foreach $groups as $group}}
				<li role="menuitem" class="sidebar-group-li group-{{$group.id}}">
					{{if ! $newgroup}}<span class="notify badge pull-right"></span>{{/if}}
					{{if $group.cid}}
						<input type="checkbox"
							class="{{if $group.selected}}ticked{{else}}unticked {{/if}} action"
							onclick="return contactgroupChangeMember(this, '{{$group.id}}','{{$group.cid}}');"
							{{if $group.ismember}}checked="checked"{{/if}}
						/>
					{{/if}}
					{{if $group.edit}}
						<a class="groupsideedit" href="{{$group.edit.href}}" title="{{$edittext}}">
							<span id="edit-sidebar-group-element-{{$group.id}}" class="group-edit-icon iconspacer small-pencil"><span class="sr-only">{{$edittext}}</span></span>
						</a>
					{{/if}}
					<a id="sidebar-group-element-{{$group.id}}" class="sidebar-group-element {{if $group.selected}}group-selected{{/if}}" href="{{$group.href}}">{{$group.text}}</a>
				</li>
			{{/foreach}}
		</ul>
	</div>

	{{if $newgroup}}
	<div id="sidebar-new-group">
		<a onclick="javascript:$('#group-new-form').fadeIn('fast');return false;">{{$createtext}}</a>
		<form id="group-new-form" action="group/new" method="post" style="display:none;">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input name="groupname" id="id_groupname" placeholder="{{$creategroup}}">
		</form>
	</div>
	{{else}}
	<div id="sidebar-edit-groups"><a href="{{$grouppage}}">{{$editgroupstext}}</a></div>
	{{/if}}

	{{if $ungrouped}}<div id="sidebar-ungrouped"><a class="{{if $ungrouped_selected}}group-selected{{/if}}" href="nogroup">{{$ungrouped}}</a></div>{{/if}}
</div>
<script>
initWidget('group-sidebar', 'group-sidebar-inflated');
</script>
