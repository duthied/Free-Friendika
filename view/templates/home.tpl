
{{* custom content from hook will replace everything. *}}
{{if $content != '' }}
	{{$content nofilter}}
{{else}}

	{{if $customhome != false }}
		{{include file="$customhome"}}
	{{else}}
		{{$defaultheader nofilter}}
	{{/if}}

	{{$login nofilter}}
{{/if}}
