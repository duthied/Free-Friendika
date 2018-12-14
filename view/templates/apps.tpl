
<h3>{{$title}}</h3>

<ul>
	{{foreach $apps as $ap}}
	<li>{{$ap nofilter}}</li>
	{{/foreach}}
</ul>
