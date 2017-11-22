
<div class="tagblock widget">
	<h3>{{$title}}</h3>

	<div class="tag-cloud">
		{{foreach $tags as $tag}}
		<span class="tags">
			<span class="tag{{$tag.level}}">#</span><a href="search?f=&tag={{$tag.url}}" class="tag{{$tag.level}}">{{$tag.name}}</a>
		</span>
		{{/foreach}}
	</div>
</div>
