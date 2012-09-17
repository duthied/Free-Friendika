 <script src="$baseurl/view/theme/quattro/jquery.tools.min.js"></script>
 
{{inc field_select.tpl with $field=$color}}{{endinc}}

{{inc field_select.tpl with $field=$align}}{{endinc}}

<input type="range" name="rage1" value="0" min="-1" max="1" step="0.01"  />


<div class="settings-submit-wrapper">
	<input type="submit" value="$submit" class="settings-submit" name="quattro-settings-submit" />
</div>

<script>
    $(":range").rangeinput();
</script>