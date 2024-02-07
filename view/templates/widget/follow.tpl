
<div id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>
	<div id="connect-desc">{{$desc nofilter}}</div>
	<form action="contact/follow" method="get">
		<input id="side-follow-url" type="text" name="url" value="{{$value}}" size="24" placeholder="{{$hint}}" title="{{$hint}}" /><input id="side-follow-submit" type="submit" name="submit" value="{{$follow}}" />
	</form>
</div>

