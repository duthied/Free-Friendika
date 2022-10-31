<form action="uimport" method="post" id="uimport-form" enctype="multipart/form-data">
	<h2>{{$import.title}}</h2>
	<p>{{$import.intro}}</p>
	<p>{{$import.instruct}}</p>
	<p><b>{{$import.warn}}</b></p>

	{{include file="field_custom.tpl" field=$import.field}}

	<div id="register-submit-wrapper">
		<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary">{{$regbutt}}</button>
	</div>
	<div id="register-submit-end"></div>
</form>
