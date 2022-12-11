<div class="generic-page-wrapper">
	<h2>{{$l10n.header}}</h2>
	<p>{{$l10n.subheader}}</p>
	<div id="prvmail-wrapper">
		<form id="prvmail-form" action="profile/{{$nickname}}/unkmail" method="post">
            {{include file="field_input.tpl" field=$to}}

            {{include file="field_input.tpl" field=$subject}}

            {{include file="field_textarea.tpl" field=$body}}

			<div id="prvmail-submit-wrapper">
				<button type="submit" id="prvmail-submit" class="btn btn-primary" name="submit">
					{{$l10n.submit}}
				</button>
				<div id="prvmail-link-wrapper">
					<div id="prvmail-link" class="icon border link" title="{{$l10n.insert}}" onclick="jotGetLink();"></div>
				</div>
				<div id="prvmail-rotator-wrapper">
					<img id="prvmail-rotator" src="images/rotator.gif" alt="{{$l10n.wait}}" title="{{$l10n.wait}}" style="display: none;"/>
				</div>
			</div>
			<div id="prvmail-end"></div>
		</form>
	</div>
</div>
