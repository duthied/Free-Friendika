
<div id="contact-edit-wrapper" >

	{{* Insert Tab-Nav *}}
	{{$tab_str nofilter}}


	<div id="contact-edit-nav-wrapper" >
		<form action="contact/{{$contact_id}}" method="post" >
			<div id="contact-edit-links">
				<div id="contact-edit-status-wrapper">
					<span id="contact-edit-contact-status">{{$contact_status}}</span>

					{{* This is the Action menu where contact related actions like 'ignore', 'hide' can be performed *}}
					<div id="contact-edit-actions">
						<a class="btn" id="contact-edit-actions-button">{{$contact_action_button}}</a>

						<ul role="menu" aria-haspopup="true" id="contact-actions-menu" class="menu-popup" >
							{{if $lblsuggest}}<li role="menuitem"><a  href="#" title="{{$contact_actions.suggest.title}}" onclick="window.location.href='{{$contact_actions.suggest.url}}'; return false;">{{$contact_actions.suggest.label}}</a></li>{{/if}}
							{{if $poll_enabled}}<li role="menuitem"><a  href="#" title="{{$contact_actions.update.title}}" onclick="window.location.href='{{$contact_actions.update.url}}'; return false;">{{$contact_actions.update.label}}</a></li>{{/if}}
							<li class="divider"></li>
							<li role="menuitem"><a  href="#" title="{{$contact_actions.block.title}}" onclick="window.location.href='{{$contact_actions.block.url}}'; return false;">{{$contact_actions.block.label}}</a></li>
							<li role="menuitem"><a  href="#" title="{{$contact_actions.ignore.title}}" onclick="window.location.href='{{$contact_actions.ignore.url}}'; return false;">{{$contact_actions.ignore.label}}</a></li>
							{{if $contact_actions.archive.url}}<li role="menuitem"><a  href="#" title="{{$contact_actions.archive.title}}" onclick="window.location.href='{{$contact_actions.archive.url}}'; return false;">{{$contact_actions.archive.label}}</a></li>{{/if}}
							{{if $contact_actions.delete.url}}<li role="menuitem"><a  href="{{$contact_actions.delete.url}}" title="{{$contact_actions.delete.title}}" onclick="return confirmDelete();">{{$contact_actions.delete.label}}</a></li>{{/if}}
						</ul>
					</div>

					{{* Block with status information about the contact *}}
					<ul>
						{{if $relation_text}}<li><div id="contact-edit-rel">{{$relation_text}}</div></li>{{/if}}

						{{if $poll_enabled}}
							<li><div id="contact-edit-last-update-text">{{$lastupdtext}} <span id="contact-edit-last-updated">{{$last_update}}</span></div>
							{{if $poll_interval}}
								<span id="contact-edit-poll-text">{{$updpub}}</span> {{$poll_interval nofilter}}
							{{/if}}
							</li>
						{{/if}}

						{{if $lost_contact}}<li><div id="lost-contact-message">{{$lost_contact}}</div></li>{{/if}}
						{{if $insecure}}<li><div id="insecure-message">{{$insecure}}</div></li>	{{/if}}
						{{if $blocked && !$pending}}<li><div id="block-message">{{$blocked}}</div></li>{{/if}}
						{{if $pending}}<li><div id="pending-message">{{$pending}}</div></li>{{/if}}
						{{if $ignored}}<li><div id="ignore-message">{{$ignored}}</div></li>{{/if}}
						{{if $archived}}<li><div id="archive-message">{{$archived}}</div></li>{{/if}}
					</ul>
				</div> {{* End of contact-edit-status-wrapper *}}

				{{* Some information about the contact from the profile *}}
				<dl><dt>{{$profileurllabel}}</dt><dd><a target="blank" href="{{$url}}">{{$profileurl}}</a></dd></dl>
				{{if $location}}<dl><dt>{{$location_label}}</dt><dd>{{$location nofilter}}</dd></dl>{{/if}}
				{{if $xmpp}}<dl><dt>{{$xmpp_label}}</dt><dd>{{$xmpp}}</dd></dl>{{/if}}
				{{if $keywords}}<dl><dt>{{$keywords_label}}</dt><dd>{{$keywords}}</dd></dl>{{/if}}
				{{if $about}}<dl><dt>{{$about_label}}</dt><dd>{{$about nofilter}}</dd></dl>{{/if}}
			</div>{{* End of contact-edit-links *}}

			<div id="contact-edit-links-end"></div>

			<hr />

			{{if $contact_settings_label}}
			<h4 id="contact-edit-settings-label" class="fakelink" onclick="openClose('contact-edit-settings')">{{$contact_settings_label}}</h4>
			<div id="contact-edit-settings">
				<input type="hidden" name="contact_id" value="{{$contact_id}}">

					<div id="contact-edit-end" ></div>
					{{include file="field_checkbox.tpl" field=$notify}}
					{{if $fetch_further_information}}
						{{include file="field_select.tpl" field=$fetch_further_information}}
						{{if $fetch_further_information.2 == 2 || $fetch_further_information.2 == 3}} {{include file="field_textarea.tpl" field=$ffi_keyword_denylist}} {{/if}}
					{{/if}}
					{{include file="field_checkbox.tpl" field=$hidden}}

				<div id="contact-edit-info-wrapper">
					<h4>{{$lbl_info1}}</h4>
					<textarea id="contact-edit-info" rows="8" cols="60" name="info">{{$info}}</textarea>
				</div>
				<div id="contact-edit-info-end"></div>

				{{if $reason}}
				<div id="contact-info-wrapper">
					<h4>{{$lbl_info2}}</h4>
					<p>{{$reason}}</p>
				</div>
				<div id="contact-info-end"></div>
				{{/if}}
			</div>
			<input class="contact-edit-submit" type="submit" name="submit" value="{{$submit}}" />
			{{/if}}

			<div class="contact-edit-submit-end clearfix"></div>

		</form>{{* End of the form *}}
	</div>{{* End of contact-edit-nav-wrapper *}}
</div>
