<div id="trending-tags-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<ul>
{{section name=ol loop=$tags max=10}}
		<li><a href="search?tag={{$tags[ol].term}}">#{{$tags[ol].term}}</a></li>
{{/section}}
	</ul>
{{if $tags|count > 10}}
	<details>
		<summary>{{$more}}</summary>
		<ul>
	{{section name=ul loop=$tags start=10}}
			<li><a href="search?tag={{$tags[ul].term}}">#{{$tags[ul].term}}</a></li>
	{{/section}}
		</ul>
	</details>
{{/if}}
</div>
