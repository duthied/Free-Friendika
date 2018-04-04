<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title }}

	<div id="remove-account-wrapper">
		<div id="remove-account-desc">{{$desc}}</div>

		<form action="{{$basedir}}/removeme" autocomplete="off" method="post" >
			<input type="hidden" name="verify" value="{{$hash}}" />

			<div id="remove-account-pass-wrapper" class="form-group">
				<label id="remove-account-pass-label" for="remove-account-pass">{{$passwd}}</label>
				<input type="password" id="remove-account-pass" class="form-control" name="qxz_password" />
			</div>
			<div id="remove-account-pass-end"></div>

			<div class="form-group pull-right settings-submit-wrapper" >
				<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}"><i class="fa fa-trash fa-fw"></i>&nbsp;{{$submit}}</button>
			</div>
			<div class="clear"></div>
		</form>
	</div>
</div>

