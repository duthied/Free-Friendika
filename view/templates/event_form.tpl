
<h3>{{$title}}</h3>

<p>
{{$desc}}
</p>

<form id="event-edit-form" action="{{$post}}" method="post" >

<input type="hidden" name="event_id" value="{{$eid}}" />
<input type="hidden" name="cid" value="{{$cid}}" />
<input type="hidden" name="uri" value="{{$uri}}" />
<input type="hidden" name="preview" id="event-edit-preview" value="0" />

{{$s_dsel}}

{{$f_dsel}}

{{include file="field_checkbox.tpl" field=$nofinish}}

{{include file="field_checkbox.tpl" field=$adjust}}

{{include file="field_input.tpl" field=$summary}}


<div id="event-desc-text">{{$d_text}}</div>
<textarea id="event-desc-textarea" name="desc">{{$d_orig}}</textarea>


<div id="event-location-text">{{$l_text}}</div>
<textarea id="event-location-textarea" name="location">{{$l_orig}}</textarea>

<div id="event-location-break"></div>

{{if ! $eid}}
{{include file="field_checkbox.tpl" field=$share}}
{{/if}}

{{$acl}}

<div class="clear"></div>
<input id="event-edit-preview" type="submit" name="preview" value="{{$preview|escape:'html'}}" onclick="doEventPreview(); return false;" />
<input id="event-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
</form>

