
<div class="field custom">
<label for="{{$id}}" id="circle-selection-lbl">{{$label}}</label>
<select name="{{$id}}" id="{{$id}}">
{{foreach $circles as $circle}}
<option value="{{$circle.id}}"{{if $circle.selected}} selected="selected"{{/if}}>{{$circle.name}}</option>
{{/foreach}}
</select>
</div>
