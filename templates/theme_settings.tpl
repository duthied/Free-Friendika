<link rel="stylesheet" href="{{$baseurl}}/view/theme/frio/frameworks/jRange/jquery.range.css" type="text/css" media="screen" />
<script src="{{$baseurl}}/view/theme/quattro/jquery.tools.min.js"></script>
<script src="{{$baseurl}}/view/theme/frio/frameworks/jRange/jquery.range.js"></script>

{{include file="field_select.tpl" field=$schema}}

{{include file="field_colorinput.tpl" field=$nav_bg}}
{{include file="field_colorinput.tpl" field=$nav_icon_color}}
{{include file="field_colorinput.tpl" field=$link_color}}

{{include file="field_colorinput.tpl" field=$bgcolor}}

{{* The slider for the content opacity - We use no template for this since it is only used at this page *}}
<div class="form-group field input color">
	<label for="id_{{$contentbg_transp.0}}" id="label_{{$contentbg_transp.0}}">{{$contentbg_transp.1}}</label>
	<input type="hidden" class="form-control color slider-input" name="{{$contentbg_transp.0}}" id="{{$contentbg_transp.0}}" type="text" value="{{$contentbg_transp.2}}">
	<span id="help_{{$contentbg_transp.0}}" class="help-block">{{$contentbg_transp.3}}</span>
	<div id="end_{{$contentbg_transp.0}}" class="field_end"></div>
</div>

{{include file="field_colorinput.tpl" field=$background_image}}

<div id="frio_bg_image_options" style="display: none;">
{{foreach $bg_image_options as $options}}
	{{include file="field_radio.tpl" field=$options}}
{{/foreach}}
</div>

<script>
	$(function(){
		$("#frio_nav_bg, #frio_nav_icon_color, #frio_background_color, #frio_link_color").colorpicker({format: 'hex', align: 'left'});

		// show image options when user user starts to type the address of the image
		if($("#id_frio_background_image").val() == "") {
			$("#id_frio_background_image").on('input',function(e){
				$("#frio_bg_image_options").show();
			});
		}

		// show the image options is there is allready an image
		if($("#id_frio_background_image").val().length != 0) {
				$("#frio_bg_image_options").show();
		}

		$('.slider-input').jRange({
			from: 0,
			to: 100,
			step: 1,
			scale: [0,10,20,30,40,50,60,70,80,90,100],
			format: '%s',
			width: 720,
			showLabels: true,
			snap: true
		});

	});
</script>

<div class="settings-submit-wrapper pull-right">
	<button type="submit" value="{{$submit}}" class="settings-submit btn btn-primary" name="frio-settings-submit"><i class="fa fa-slideshare"></i>&nbsp;{{$submit}}</button>
</div>
<div class="clearfix"></div>

<script>
    
    $(".inputRange").rangeinput();
</script>
