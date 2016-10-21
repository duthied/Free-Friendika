
<link rel='stylesheet' type='text/css' href='{{$baseurl}}/library/fullcalendar/fullcalendar.css' />
<link rel='stylesheet' type='text/css' href='view/theme/frio/css/mod_events.css' />
<script type="text/javascript" src="{{$baseurl}}/library/moment/moment.min.js"></script>
<script type="text/javascript" src="{{$baseurl}}/library/moment/locales.min.js"></script>
<script type="text/javascript" src="{{$baseurl}}/library/fullcalendar/fullcalendar.min.js"></script>
<script type="text/javascript" src="view/theme/frio/js/mod_events.js"></script>


<script language="javascript" type="text/javascript">
	// pass php translation strings to js variables/arrays so we can make use of it in js files
	aStr.monthNames = ['{{$i18n.January}}','{{$i18n.February}}','{{$i18n.March}}','{{$i18n.April}}','{{$i18n.May}}','{{$i18n.June}}','{{$i18n.July}}','{{$i18n.August}}','{{$i18n.September}}','{{$i18n.October}}','{{$i18n.November}}','{{$i18n.December}}'];
	aStr.monthNamesShort = ['{{$i18n.Jan}}','{{$i18n.Feb}}','{{$i18n.Mar}}','{{$i18n.Apr}}','{{$i18n.May}}','{{$i18n.Jun}}','{{$i18n.Jul}}','{{$i18n.Aug}}','{{$i18n.Sep}}','{{$i18n.Oct}}','{{$i18n.Nov}}','{{$i18n.Dec}}'];
	aStr.monthNamesShort = ['{{$i18n.Jan}}','{{$i18n.Feb}}','{{$i18n.Mar}}','{{$i18n.Apr}}','{{$i18n.May}}','{{$i18n.Jun}}','{{$i18n.Jul}}','{{$i18n.Aug}}','{{$i18n.Sep}}','{{$i18n.Oct}}','{{$i18n.Nov}}','{{$i18n.Dec}}'];
	aStr.dayNames = ['{{$i18n.Sunday}}','{{$i18n.Monday}}','{{$i18n.Tuesday}}','{{$i18n.Wednesday}}','{{$i18n.Thursday}}','{{$i18n.Friday}}','{{$i18n.Saturday}}'];
	aStr.dayNamesShort = ['{{$i18n.Sun}}','{{$i18n.Mon}}','{{$i18n.Tue}}','{{$i18n.Wed}}','{{$i18n.Thu}}','{{$i18n.Fri}}','{{$i18n.Sat}}'];

	aStr.firstDay = '{{$i18n.firstDay|escape:'quotes'}}';
	aStr.today = '{{$i18n.today|escape:'quotes'}}';
	aStr.month = '{{$i18n.month|escape:'quotes'}}';
	aStr.week = '{{$i18n.week|escape:'quotes'}}';
	aStr.day = '{{$i18n.day|escape:'quotes'}}';

	aStr.allday = '{{$i18n.allday|escape:'quotes'}}';
	aStr.noevent = '{{$i18n.noevent|escape:'quotes'}}';

	aStr.dtstartLabel = '{{$i18n.dtstart_label|escape:'quotes'}}';
	aStr.dtendLabel = '{{$i18n.dtend_label|escape:'quotes'}}';
	aStr.locationLabel = '{{$i18n.location_label|escape:'quotes'}}';

	var moduleUrl = '{{$module_url}}';
	var modparams = {{$modparams}}

</script>

{{if $editselect != 'none'}}
<script language="javascript" type="text/javascript"
	  src="{{$baseurl}}/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">


	tinyMCE.init({
		theme : "advanced",
		mode : "textareas",
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		theme_advanced_resizing : true,
		gecko_spellcheck : true,
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		//force_p_newlines : false,
		//force_br_newlines : true,
		forced_root_block : 'div',
		content_css: "{{$baseurl}}/view/custom_tinymce.css",
		theme_advanced_path : false,
		setup : function(ed) {
			ed.onInit.add(function(ed) {
				ed.pasteAsPlainText = true;
			});
		}

	});

	$(document).ready(function() {
		$('.comment-edit-bb').hide();
	});
	{{else}}
	<script language="javascript" type="text/javascript">
	{{/if}}


	$(document).ready(function() {
		{{if $editselect = 'none'}}
		$("#comment-edit-text-desc").bbco_autocomplete('bbcode');
		{{/if}}

	});

</script>
