
<div id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>

	<form action="contact/follow" method="get">
		{{* The input field - For visual consistence we are using a search input field*}}
		<div class="form-group form-group-search">
			<input id="side-follow-url" class="search-input form-control form-search" type="text" name="url" value="{{$value}}" placeholder="{{$hint}}" data-toggle="tooltip" title="{{$hint}}" />
			<button id="side-follow-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$follow}}</button>
		</div>
	</form>
</div>

