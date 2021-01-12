<div id="saved-search-list" class="widget">
	<h3 class="title">{{$title}}</h3>

	<ul id="saved-search-ul">
        {{foreach $saved as $search}}
			<li class="tool {{if $search.selected}}selected{{/if}}">
				<a href="{{$search.searchpath}}" class="label">{{$search.term}}</a>
				<a href="search/saved/remove?term={{$search.encodedterm}}&amp;return_url={{$return_url}}" class="action icon s10 delete" title="{{$search.delete}}" onclick="return confirmDelete();"></a>
			</li>
        {{/foreach}}
	</ul>

    {{$searchbox nofilter}}

</div>
