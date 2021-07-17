<table>
	<thead>
		<tr>
			<td colspan="2" style="padding-top:22px;">
				{{$preamble nofilter}}
			</td>
		</tr>
	</thead>
{{if $content_allowed}}
	<tbody>
	{{if $source_photo}}
		<tr>
			<td style="padding-left:22px;padding-top:22px;width:60px;" valign="top" rowspan=3><a href="{{$source_link}}"><img style="border:0px;width:48px;height:48px;" src="{{$source_photo}}"></a></td>
			<td style="padding-top:22px;"><a href="{{$source_link}}">{{$source_name}}</a></td>
		</tr>
	{{/if}}
		<tr>
			<td style="font-weight:bold;padding-bottom:5px;">{{$title}}</td>
		</tr>
		<tr>
			<td style="padding-right:22px;">{{$htmlversion nofilter}}
		</td>
		</tr>
{{/if}}
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2" style="padding-top:11px;">
				{{$hsitelink nofilter}}
			</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-bottom:11px;">
				{{$hitemlink nofilter}}
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				{{$thanks}}
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				{{$site_admin}}
			</td>
		</tr>
	</tfoot>
</table>
