 <script src="{{$baseurl}}/view/theme/quattro/jquery.tools.min.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
 
{{include file="field_select.tpl" field=$color}}

{{include file="field_select.tpl" field=$align}}


<div class="field">
    <label for="id_{{$pfs.0}}">{{$pfs.1}}</label>
    <input type="range" class="inputRange" id="id_{{$pfs.0}}" name="{{$pfs.0}}" value="{{$pfs.2}}" min="10" max="22" step="1"  />
    <span class="field_help"></span>
</div>


<div class="field">
    <label for="id_{{$tfs.0}}">{{$tfs.1}}</label>
    <input type="range" class="inputRange" id="id_{{$tfs.0}}" name="{{$tfs.0}}" value="{{$tfs.2}}" min="10" max="22" step="1"  />
    <span class="field_help"></span>
</div>





<div class="settings-submit-wrapper">
	<input type="submit" value="{{$submit}}" class="settings-submit" name="quattro-settings-submit" />
</div>

<script>
    
    $(".inputRange").rangeinput();
</script>
