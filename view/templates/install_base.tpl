<h1><img src="{{$baseurl}}/images/friendica-32.png"> {{$title}}</h1>
<h2>{{$pass}}</h2>

<p>
	{{$info_01}}<br>
	{{$info_02}}<br>
	{{$info_03}}
</p>

<form id="install-form" action="{{$baseurl}}/install" method="post">

	<input type="hidden" name="config-php_path" value="{{$php_path}}" />
	<input type="hidden" name="pass" value="3" />

	{{include file="field_select.tpl" field=$ssl_policy}}
	<br />
	{{include file="field_input.tpl" field=$hostname}}
	<br />
	{{include file="field_input.tpl" field=$basepath}}
	<br />
	{{include file="field_input.tpl" field=$urlpath}}

	<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
