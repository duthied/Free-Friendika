
<link rel='stylesheet' type='text/css' href='{{$baseurl}}/library/fullcalendar/fullcalendar.css' />
<script language="javascript" type="text/javascript"
	  src="{{$baseurl}}/library/fullcalendar/fullcalendar.min.js"></script>

<script>

	// loads the event into a modal
	function showEvent(eventid) {
			addToModal('{{$baseurl}}/events/?id='+eventid);
	
	}

	// Load the html of the actual event and incect the output to the
	// event-edit section
	function doEventPreview() {
		$('#event-edit-preview').val(1);
		$.post('events',$('#event-edit-form').serialize(), function(data) {
			$("#event-preview").append(data);
		});
		$('#event-edit-preview').val(0);
	}

	function changeView(action, viewName) {
		$('#events-calendar').fullCalendar(action, viewName);
		var view = $('#events-calendar').fullCalendar('getView');
		$('#fc-title').text(view.title);
	}

	$(document).ready(function() {
		// start the fullCalendar
		$('#events-calendar').fullCalendar({
			firstDay: {{$i18n.firstDay}},
			monthNames: ['{{$i18n.January}}','{{$i18n.February}}','{{$i18n.March}}','{{$i18n.April}}','{{$i18n.May}}','{{$i18n.June}}','{{$i18n.July}}','{{$i18n.August}}','{{$i18n.September}}','{{$i18n.October}}','{{$i18n.November}}','{{$i18n.December}}'],
			monthNamesShort: ['{{$i18n.Jan}}','{{$i18n.Feb}}','{{$i18n.Mar}}','{{$i18n.Apr}}','{{$i18n.May}}','{{$i18n.Jun}}','{{$i18n.Jul}}','{{$i18n.Aug}}','{{$i18n.Sep}}','{{$i18n.Oct}}','{{$i18n.Nov}}','{{$i18n.Dec}}'],
			dayNames: ['{{$i18n.Sunday}}','{{$i18n.Monday}}','{{$i18n.Tuesday}}','{{$i18n.Wednesday}}','{{$i18n.Thursday}}','{{$i18n.Friday}}','{{$i18n.Saturday}}'],
			dayNamesShort: ['{{$i18n.Sun}}','{{$i18n.Mon}}','{{$i18n.Tue}}','{{$i18n.Wed}}','{{$i18n.Thu}}','{{$i18n.Fri}}','{{$i18n.Sat}}'],
			buttonText: {
				prev: "<span class='fc-text-arrow'>&lsaquo;</span>",
				next: "<span class='fc-text-arrow'>&rsaquo;</span>",
				prevYear: "<span class='fc-text-arrow'>&laquo;</span>",
				nextYear: "<span class='fc-text-arrow'>&raquo;</span>",
				today: '{{$i18n.today}}',
				month: '{{$i18n.month}}',
				week: '{{$i18n.week}}',
				day: '{{$i18n.day}}'
			},
			events: '{{$baseurl}}/events/json/',
			header: {
				left: '',
			//	center: 'title',
				right: ''
			},			
			timeFormat: 'H(:mm)',
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			},
			loading: function(isLoading, view) {
				if(!isLoading) {
					$('td.fc-day').dblclick(function() { window.location.href='/events/new?start='+$(this).data('date'); });
				}
			},
			
			eventRender: function(event, element, view) {
				//console.log(view.name);
				if (event.item['author-name']==null) return;
				switch(view.name){
					case "month":
					element.find(".fc-event-title").html(
						"<img src='{0}' style='height:10px;width:10px'>{1} : {2}".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.title
					));
					break;
					case "agendaWeek":
					element.find(".fc-event-title").html(
						"<img src='{0}' style='height:12px; width:12px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
					case "agendaDay":
					element.find(".fc-event-title").html(
						"<img src='{0}' style='height:24px;width:24px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
				}
			}
			
		})
		
		// center on date
		var args=location.href.replace(baseurl,"").split("/");
		if (args.length>=4) {
			$("#events-calendar").fullCalendar('gotoDate',args[2] , args[3]-1);
		} 

		// echo the title
		var view = $('#events-calendar').fullCalendar('getView');
		$('#fc-title').text(view.title);

		// show event popup
		var hash = location.hash.split("-")
		if (hash.length==2 && hash[0]=="#link") showEvent(hash[1]);
		
	});
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

		// go to the permissions tab if the checkbox is checked
		$('body').on("change", "#id_share", function() {

			if ($('#id_share').is(':checked') && !( $('#id_share').attr("disabled"))) { 
				$('#acl-wrapper').show();
				$("a#event-perms-lnk").parent("li").show();
				toggleEventNav("a#event-perms-lnk");
				eventAclActive();
			}
			else {
				$('#acl-wrapper').hide();
				$("a#event-perms-lnk").parent("li").hide();
			}
		}).trigger('change');

		// disable the finish time input if the user disable it
		$('body').on("change", "#id_nofinish", function() {
			enableDisableFinishDate()
		}).trigger('change');

		// js for the permission sextion
		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-public').hide();
			});
			if(selstr == null) {
				$('#jot-public').show();
			}

		}).trigger('change');

		// Change the event nav menu.tabs on click
		$("body").on("click", "#event-nav > li > a", function(e){
			e.preventDefault();
			toggleEventNav(this);
		});

		// this is experimental. We maybe can make use of it to inject
		// some js code while the event modal opens
		//$('body').on('show.bs.modal', function () {
		//	enableDisableFinishDate();
		//});

		// clear some elements (e.g. the event-preview container) when
		// selecting a event nav link so it don't appear more than once
		$('body').on("click", "#event-nav a", function(e) {
			$("#event-preview").empty();
			e.preventDefault();
		});


	});

</script>

<script>
	// the following functions show/hide the specific event-edit content 
	// in dependence of the selected nav
	function eventAclActive() {
		$("#event-edit-wrapper, #event-preview").hide();
		$("#event-acl-wrapper").show();
	}


	function eventPreviewActive() {
		$("#event-acl-wrapper, #event-edit-wrapper").hide();
		$("#event-preview").show();
		doEventPreview();
	}

	function eventEditActive() {
		$("#event-acl-wrapper, #event-preview").hide();
		$("#event-edit-wrapper").show();

		//make sure jot text does have really the active class (we do this because there are some
		// other events which trigger jot text
		toggleEventNav($("#event-edit-lnk"));
	}

	// Give the active "event-nav" list element the class "active"
	function toggleEventNav (elm) {
		// select all li of #event-nav and remove the active class
		$(elm).closest("#event-nav").children("li").removeClass("active");
		// add the active class to the parent of the link which was selected
		$(elm).parent("li").addClass("active");
	}

	// this function load the content of the edit url into a modal
	function eventEdit(url) {
		var char = qOrAmp(url);
		url = url + char + 'mode=none';

		$.get(url, function(data) {
			$("#modal-body").empty();
			$("#modal-body").append(data);
		}).done(function() {
			loadModalTitle();
		});
	}

	// disable the input for the finish date if it is not available
	function enableDisableFinishDate() {
		if( $('#id_nofinish').is(':checked'))
			$('#id_finish_text').prop("disabled", true);
		else
			$('#id_finish_text').prop("disabled", false);
	}
</script>
