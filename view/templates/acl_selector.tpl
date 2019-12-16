<div id="acl-wrapper">
	<div class="panel-group" id="visibility-accordion" role="tablist" aria-multiselectable="true">
		<div class="panel panel-success">
			<label class="panel-heading{{if $visibility != 'public'}} collapsed{{/if}}" id="visibility-public-heading" aria-expanded="{{if $visibility == 'public'}}true{{else}}false{{/if}}">
				<input type="radio" name="visibility" id="visibility-public" value="public" tabindex="14" {{if $visibility == 'public'}}checked{{/if}}>
				<i class="fa fa-globe"></i> {{$public_title}}
			</label>
			<fieldset id="visibility-public-panel" class="panel-collapse collapse{{if $visibility == 'public'}} in{{/if}}" role="tabpanel" aria-labelledby="visibility-public-heading" {{if $visibility != 'public'}}disabled{{/if}}>
				<div class="panel-body">
					<p>{{$public_desc}}</p>
	                {{if $for_federation}}
		                {{if $user_hidewall}}
			                <h4>{{$jotnets_summary}}</h4>
	                        {{$jotnets_disabled_label}}
		                {{elseif $jotnets_fields}}
		                    {{if $jotnets_fields|count < 3}}
								<div class="profile-jot-net">
		                    {{else}}
								<details class="profile-jot-net">
								<summary>{{$jotnets_summary}}</summary>
		                    {{/if}}

		                    {{foreach $jotnets_fields as $jotnets_field}}
		                        {{if $jotnets_field.type == 'checkbox'}}
		                            {{include file="field_checkbox.tpl" field=$jotnets_field.field}}
		                        {{elseif $jotnets_field.type == 'select'}}
		                            {{include file="field_select.tpl" field=$jotnets_field.field}}
		                        {{/if}}
		                    {{/foreach}}

		                    {{if $jotnets_fields|count >= 3}}
								</details>
		                    {{else}}
								</div>
		                    {{/if}}
			            {{/if}}
	                {{/if}}
				</div>
			</fieldset>
		</div>
		<div class="panel panel-info">
			<label class="panel-heading{{if $visibility != 'custom'}} collapsed{{/if}}" id="visibility-custom-heading" aria-expanded="{{if $visibility == 'custom'}}true{{else}}false{{/if}}">
				<input type="radio" name="visibility" id="visibility-custom" value="custom" tabindex="15" {{if $visibility == 'custom'}}checked{{/if}}>
				<i class="fa fa-lock"></i> {{$custom_title}}
			</label>
			<fieldset id="visibility-custom-panel" class="panel-collapse collapse{{if $visibility == 'custom'}} in{{/if}}" role="tabpanel" aria-labelledby="visibility-custom-heading" {{if $visibility != 'custom'}}disabled{{/if}}>
				<input type="hidden" name="group_allow" value="{{$group_allow}}"/>
				<input type="hidden" name="contact_allow" value="{{$contact_allow}}"/>
				<input type="hidden" name="group_deny" value="{{$group_deny}}"/>
				<input type="hidden" name="contact_deny" value="{{$contact_deny}}"/>
				<div class="panel-body">
					<p>{{$custom_desc}}</p>

					<div class="form-group">
						<label for="acl_allow">{{$allow_label}}</label>
						<input type="text" class="form-control input-lg" id="acl_allow">
					</div>

					<div class="form-group">
						<label for="acl_deny">{{$deny_label}}</label>
						<input type="text" class="form-control input-lg" id="acl_deny">
					</div>
				</div>
			</fieldset>
		</div>
	</div>


{{if $for_federation}}
	<div class="form-group">
		<label for="profile-jot-email" id="profile-jot-email-label">{{$emailcc}}</label>
		<input type="text" name="emailcc" id="profile-jot-email" class="form-control" title="{{$emtitle}}" />
	</div>
	<div id="profile-jot-email-end"></div>
{{/if}}
</div>
<script type="text/javascript">
	$(function() {
		let $acl_allow_input = $('#acl_allow');
		let $contact_allow_input = $('[name=contact_allow]');
		let $group_allow_input = $('[name=group_allow]');
		let $acl_deny_input = $('#acl_deny');
		let $contact_deny_input = $('[name=contact_deny]');
		let $group_deny_input = $('[name=group_deny]');
		let $visibility_public_panel = $('#visibility-public-panel');
		let $visibility_custom_panel = $('#visibility-custom-panel');
		let $visibility_public_radio = $('#visibility-public');
		let $visibility_custom_radio = $('#visibility-custom');

		// Frio specific
		if ($.fn.collapse) {
			$visibility_public_panel.collapse({parent: '#visibility-accordion', toggle: false});
			$visibility_custom_panel.collapse({parent: '#visibility-accordion', toggle: false});
		}

		$visibility_public_radio.on('change', function (e) {
			if ($.fn.collapse) {
				$visibility_public_panel.collapse('show');
			}

			$visibility_public_panel.prop('disabled', false);
			$visibility_custom_panel.prop('disabled', true);

			$('.profile-jot-net input[type=checkbox]').each(function() {
				// Restores checkbox state if it had been saved
				if ($(this).attr('data-checked') !== undefined) {
					$(this).prop('checked', $(this).attr('data-checked') === 'true');
				}
			});
			$('.profile-jot-net input').attr('disabled', false);
		});

		$visibility_custom_radio.on('change', function(e) {
			if ($.fn.collapse) {
				$visibility_custom_panel.collapse('show');
			}

			$visibility_public_panel.prop('disabled', true);
			$visibility_custom_panel.prop('disabled', false);

			$('.profile-jot-net input[type=checkbox]').each(function() {
				// Saves current checkbox state
				$(this)
					.attr('data-checked', $(this).prop('checked'))
					.prop('checked', false);
			});
			$('.profile-jot-net input').attr('disabled', 'disabled');
		});

		// Custom visibility tags inputs
		let acl_groups = new Bloodhound({
			local: {{$acl_groups|@json_encode nofilter}},
			identify: function(obj) { return obj.type + '-' + obj.id.toString(); },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
		});
		let acl_contacts = new Bloodhound({
			local: {{$acl_contacts|@json_encode nofilter}},
			identify: function(obj) { return obj.type + '-' + obj.id.toString(); },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name', 'addr']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
		});
		let acl = new Bloodhound({
			local: {{$acl_list|@json_encode nofilter}},
			identify: function(obj) { return obj.type + '-' + obj.id.toString(); },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name', 'addr']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			sorter: function (itemA, itemB) {
				if (itemA.name === itemB.name) {
					return 0;
				} else if (itemA.name > itemB.name) {
					return 1;
				} else {
					return -1;
				}
			},
		});
		acl.initialize();

		let suggestionTemplate = function (item) {
			return '<div><img src="' + item.micro + '" alt="" style="float: left; width: auto; height: 2.8em; margin-right: 0.5em;"> <strong>' + item.name + '</strong><br /><em>' + item.addr + '</em></div>';
		};

		$acl_allow_input.tagsinput({
			confirmKeys: [13, 44],
			freeInput: false,
			tagClass: function(item) {
				switch (item.type) {
					case 'group'   : return 'label label-primary';
					case 'contact'  :
					default:
						return 'label label-info';
				}
			},
			itemValue: function (item) { return item.type + '-' + item.id.toString(); },
			itemText: 'name',
			itemThumb: 'micro',
			itemTitle: function(item) {
				return item.addr;
			},
			typeaheadjs: {
				name: 'contacts',
				displayKey: 'name',
				templates: {
					suggestion: suggestionTemplate
				},
				source: acl.ttAdapter()
			}
		});

		$acl_deny_input
			.tagsinput({
				confirmKeys: [13, 44],
				freeInput: false,
				tagClass: function(item) {
					switch (item.type) {
						case 'group'   : return 'label label-primary';
						case 'contact'  :
						default:
							return 'label label-info';
					}
				},
				itemValue: function (item) { return item.type + '-' + item.id.toString(); },
				itemText: 'name',
				itemThumb: 'micro',
				itemTitle: function(item) {
					return item.addr;
				},
				typeaheadjs: {
					name: 'contacts',
					displayKey: 'name',
					templates: {
						suggestion: suggestionTemplate
					},
					source: acl.ttAdapter()
				}
			});

		// Import existing ACL into the tags input fields.

		$group_allow_input.val().split(',').forEach(function (group_id) {
			$acl_allow_input.tagsinput('add', acl_groups.get('group-' + group_id)[0]);
		});
		$contact_allow_input.val().split(',').forEach(function (contact_id) {
			$acl_allow_input.tagsinput('add', acl_contacts.get('contact-' + contact_id)[0]);
		});
		$group_deny_input.val().split(',').forEach(function (group_id) {
			$acl_deny_input.tagsinput('add', acl_groups.get('group-' + group_id)[0]);
		});
		$contact_deny_input.val().split(',').forEach(function (contact_id) {
			$acl_deny_input.tagsinput('add', acl_contacts.get('contact-' + contact_id)[0]);
		});

		// Anti-duplicate callback + acl fields value generation

		$acl_allow_input.on('itemAdded', function (event) {
			// Removes duplicate in the opposite acl box
			$acl_deny_input.tagsinput('remove', event.item);

			// Update the real acl field
			$group_allow_input.val('');
			$contact_allow_input.val('');
			[].forEach.call($acl_allow_input.tagsinput('items'), function (item) {
				if (item.type === 'group') {
					$group_allow_input.val($group_allow_input.val() + ',' + item.id);
				} else {
					$contact_allow_input.val($contact_allow_input.val() + ',' + item.id);
				}
			});
		});

		$acl_deny_input.on('itemAdded', function (event) {
			// Removes duplicate in the opposite acl box
			$acl_allow_input.tagsinput('remove', event.item);

			// Update the real acl field
			$group_deny_input.val('');
			$contact_deny_input.val('');
			[].forEach.call($acl_deny_input.tagsinput('items'), function (item) {
				if (item.type === 'group') {
					$group_deny_input.val($group_allow_input.val() + ',' + item.id);
				} else {
					$contact_deny_input.val($contact_allow_input.val() + ',' + item.id);
				}
			});
		});
	});
</script>
