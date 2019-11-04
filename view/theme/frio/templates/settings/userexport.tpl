
<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}

	{{foreach $options as $o}}
	<dl>
		<dt><a href="{{$o.0}}">{{$o.1}}</a></dt>
		<dd>{{$o.2}}</dd>
	</dl>
	{{/foreach}}
</div>
