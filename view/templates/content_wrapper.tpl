
<div {{if $name}}id="{{$name}}-wrapper"{{/if}} class="general-content-wrapper">
	{{* give different possibilities for the size of the heading *}}
	{{if $title && $title_size}}
		<h{{$title_size}} {{if $name}}id="{{$name}}-heading"{{/if}}>{{$title}}</h{{$title_size}}>
	{{elseif $title}}
	{{include file="section_title.tpl"}}
	{{/if}}

	{{* output the content *}}
	{{$content nofilter}}

</div>
