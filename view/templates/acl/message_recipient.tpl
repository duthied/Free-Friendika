<select name="recipient" class="form-control input-lg" id="recipient" required>
	<option></option>
	{{foreach $contacts as $contact}}
		<option value="{{$contact.id}}"{{if $contact.id == $selected}} selected{{/if}}>{{$contact.name}}</option>
	{{/foreach}}
</select>
<script type="text/javascript">
	$(function() {
		let $recipient_input = $('[name="recipient"]');

		let acl = new Bloodhound({
			local: {{$contacts_json nofilter}},
			identify: function(obj) { return obj.id.toString(); },
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
			return '<div><img src="' + item.micro + '" alt="" style="float: left; width: auto; height: 2.8em; margin-right: 0.5em;"><p style="margin-left: 3.3em;"><strong>' + item.name + '</strong><br /><em>' + item.addr + '</em></p></div>';
		};

		$recipient_input.tagsinput({
			confirmKeys: [13, 44],
			freeInput: false,
			tagClass: 'label label-info',
			itemValue: function (item) { return item.id.toString(); },
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

		{{if $selected}}
		// Import existing ACL into the tags input fields.
		$recipient_input.tagsinput('add', acl.get({{$selected}})[0]);
		{{/if}}
	});
</script>
