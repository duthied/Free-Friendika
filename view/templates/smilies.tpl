<div id="smilies" class="generic-page-wrapper">
	<div class="smiley-sample">
		{{for $i=0 to $count}}
		<dl>
			<dt>{{$smilies.texts[$i] nofilter}}</dt>
			<dd>{{$smilies.icons[$i] nofilter}}</dd>
		</dl>
		{{/for}}
	</div>
</div>
