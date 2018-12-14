

<h1><img src="{{$baseurl}}/images/friendica-32.png"> {{$title}}</h1>
<h2>{{$pass}}</h2>


<form id="install-form" action="{{$baseurl}}/install" method="post">

<input type="hidden" name="phpath" value="{{$phpath}}" />
<input type="hidden" name="dbhost" value="{{$dbhost}}" />
<input type="hidden" name="dbuser" value="{{$dbuser}}" />
<input type="hidden" name="dbpass" value="{{$dbpass}}" />
<input type="hidden" name="dbdata" value="{{$dbdata}}" />
<input type="hidden" name="pass" value="4" />

{{include file="field_input.tpl" field=$adminmail}}
{{$timezone}}
{{include file="field_select.tpl" field=$language}}

<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>

