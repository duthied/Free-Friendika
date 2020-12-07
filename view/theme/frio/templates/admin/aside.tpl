<script type="text/javascript">
	// update pending count //
	$(function(){
		$("nav").bind('nav-update', function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { register = ""; }
			elm.html(register);
		});
	});
</script>

{{foreach $subpages as $page}}
<div class="widget">
	<h3>{{$page.0}}</h3>
	<ul role="menu">
		{{foreach $page.1 as $item}}
		<li role="menuitem" class="{{$item.2}}">
			<a href="{{$item.0}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}"{{/if}}>
				{{$item.1}}
				{{if $name == "users"}}
				 <span id="pending-update" class="badge pull-right"></span>
				{{/if}}
			</a>
		</li>
		{{/foreach}}
	</ul>

	{{if $admin.update}}
	<ul role="menu">
		<li role="menuitem" class="{{$admin.update.2}}">
			<a href="{{$admin.update.0}}" {{if $admin.update.accesskey}}accesskey="{{$admin.update.accesskey}}"{{/if}}>
				{{$admin.update.1}}
			</a>
		</li>
	</ul>
	{{/if}}
</div>
{{/foreach}}

{{if $admin.addons_admin}}
<div class="widget">
	<h3>{{$plugadmtxt}}</h3>
	<ul role="menu">
		{{foreach $admin.addons_admin as $name => $item}}
		<li role="menuitem" class="{{$item.class}}">
			<a href="{{$item.url}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}"{{/if}}>
				{{$item.name}}
			</a>
		</li>
		{{/foreach}}
	</ul>
</div>
{{/if}}

