
<div id="contacts" class="standard-page">

	{{$tabs}}

	{{* The page headding with it's contacts counter *}}
	<h2 class="headding">{{$header}} {{if $total}} ({{$total}}) {{/if}}</h2>

	{{if $finding}}<h4>{{$finding}}</h4>{{/if}}

	{{* The search input field to search for contacts *}}
	<div id="contacts-search-wrapper">
		<form id="contacts-search-form" class="navbar-form" action="{{$cmd}}" method="get" >
			<label for="contacts-search" class="contacts-search-desc">{{$desc}}</label><br/>
			<div class="input-group">
				<input type="text" name="search" id="contacts-search" class="search-input form-control" onfocus="this.select();" value="{{$search|escape:'html'}}" />
				<div class="input-group-btn">
					<button class="btn btn-default" type="submit" name="submit" id="contacts-search-submit" value="{{$submit|escape:'html'}}"><i class="fa fa-search"></i></button>
				</div>
			</div>
		</form>
	</div>

	<hr>
	<div id="contacts-search-end"></div>

	{{* we need the form container to make batch actions work *}}
	<form action="{{$baseurl}}/contacts/batch/" method="POST">
		{{* We put the contact batch actions in a dropdown menu *}}
		<ul class="nav nav-pills preferences">
			<li class="dropdown pull-right">
				<a class="btn btn-link dropdown-toggle" type="button" id="BatchActionDropdownMenuTools" data-toggle="dropdown" aria-expanded="true">
					<i class="fa fa-angle-down"></i>
				</a>
				<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="BatchActionDropdownMenuTools">
				{{foreach $batch_actions as $n=>$l}}
					<li role="presentation">
						<input class="batch-action no-input fakelist" name="{{$n}}" value="{{$l|escape:'html'}}" type="submit">
					</li>
				{{/foreach}}
				</ul>
			</li>
		</ul>
		<div class="clear">

		{{* format each contact with the contact_template.tpl *}}
		<ul id="viewcontact_wrapper" class="viewcontact_wrapper media-list">
		{{foreach $contacts as $contact}}
			<li>{{include file="contact_template.tpl"}}</li>
		{{/foreach}}
		</ul>
		<div id="contact-edit-end"></div>
	</form>

	{{$paginate}}
</div>

<script>
 $(document).ready(function() {
  // javascript dialog to batch actions
  $(".batch-action").click(function(e){
    if (confirm($(this).attr('value')+" ?")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
  });
 
  // add javascript confirm dialog to "drop" links. Plain html url have "?confirm=1" to show confirmation form, we need to remove it
  $(".drop").each(function() {
   $(this).attr('href', $(this).attr('href').replace("confirm=1","") );
   $(this).click(function(e){
    if (confirm("{{$contact_drop_confirm}}")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
   });
   
  });
 });
 </script>

