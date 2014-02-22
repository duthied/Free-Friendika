
<div class="contact-wrapper" id="contact-entry-wrapper-{{$id}}" >
	<div class="contact-photo-wrapper" >
		<div class="contact-photo mframe" id="contact-entry-photo-{{$contact.id}}"
		onmouseover="if (typeof t{{$contact.id}} != 'undefined') clearTimeout(t{{$contact.id}}); openMenu('contact-photo-menu-button-{{$contact.id}}')" 
		onmouseout="t{{$contact.id}}=setTimeout('closeMenu(\'contact-photo-menu-button-{{$contact.id}}\'); closeMenu(\'contact-photo-menu-{{$contact.id}}\');',200)" >

			<a href="{{$contact.url}}" title="{{$contact.img_hover}}" /><img src="{{$contact.thumb}}" {{$contact.sparkle}} alt="{{$contact.name}}" /></a>

			<input type="checkbox" class="contact-select" name="contact_batch[]" value="{{$contact.id}}">
			{{if $contact.photo_menu}}
			<a href="#" rel="#contact-photo-menu-{{$contact.id}}" class="contact-photo-menu-button icon s16 menu" id="contact-photo-menu-button-{{$contact.id}}">menu</a>
			<ul class="contact-photo-menu menu-popup" id="contact-photo-menu-{{$contact.id}}">
				{{foreach $contact.photo_menu as $c}}
				{{if $c.2}}
				<li><a target="redir" href="{{$c.1}}">{{$c.0}}</a></li>
				{{else}}
				<li><a href="{{$c.1}}">{{$c.0}}</a></li>
				{{/if}}
				{{/foreach}}
			</ul>
			{{/if}}
		</div>
			
	</div>
	<div class="contact-name" id="contact-entry-name-{{$contact.id}}" >{{$contact.name}}</div>
	{{if $contact.alt_text}}<div class="contact-details" id="contact-entry-rel-{{$contact.id}}" >{{$contact.alt_text}}</div>{{/if}}
	<div class="contact-details" id="contact-entry-url-{{$contact.id}}" >{{$contact.itemurl}}</div>
	<div class="contact-details" id="contact-entry-network-{{$contact.id}}" >{{$contact.network}}</div>


</div>

