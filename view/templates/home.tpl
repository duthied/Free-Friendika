
{{* custom content from hook will replace everything. *}}
{{if $content != '' }}
	{{$content nofilter}}
{{else}}

	{{if $customhome != false }}
		{{include file="$customhome"}}
	{{else}}
		<h1>{{$defaultheader nofilter}}</h1>
	{{/if}}

	{{$login nofilter}}
{{/if}}
