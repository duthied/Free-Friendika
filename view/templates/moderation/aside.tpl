<script>
	// update pending count //
	$(function(){
		$("nav").bind('nav-update',  function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').html();
			if (register=="0") { register=""; elm.hide();} else { elm.show(); }
			elm.html(register);
		});
	});
</script>

{{foreach $subpages as $page}}
<h4>{{$page.0}}</h4>
<ul class="admin linklist" role="menu">
{{foreach $page.1 as $item}}
	<li class="admin link button {{$item.2}}" role="menuitem"><a href="{{$item.0}}">{{$item.1}}</a></li>
{{/foreach}}
</ul>
{{/foreach}}
