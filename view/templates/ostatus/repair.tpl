<div class="generic-page-wrapper">
	<h2>{{$l10n.title}}</h2>
{{if $total}}
	{{if $contact}}
		<div class="alert alert-info">
            {{$counter}} / {{$total}} : {{$contact.url}}
		</div>
	{{else}}
		<div class="alert alert-success">
			{{$l10n.done}}
		</div>
	{{/if}}
{{else}}
	<div class="alert alert-warning">
		{{$l10n.nocontacts}}
	</div>
{{/if}}
</div>
