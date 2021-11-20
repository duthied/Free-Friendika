<div class="generic-page-wrapper">
	<h1>{{$ptitle}}</h1>

	{{$nickname_block nofilter}}

	<form action="settings" id="settings-form" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			{{* The password setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="password-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#password-settings-collapse" aria-expanded="false" aria-controls="password-settings-collapse">
							{{$h_pass}}
						</button>
					</h2>
				</div>
				<div id="password-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="password-settings">
					<div class="panel-body">
						{{include file="field_password.tpl" field=$password1}}
						{{include file="field_password.tpl" field=$password2}}
						{{include file="field_password.tpl" field=$password3}}

					{{if $oid_enable}}
						{{include file="field_input.tpl" field=$openid}}
						{{include file="field_checkbox.tpl" field=$delete_openid}}
					{{/if}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The basic setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="basic-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#basic-settings-collapse" aria-expanded="false" aria-controls="basic-settings-collapse">
							{{$h_basic}}
						</button>
					</h2>
				</div>
				<div id="basic-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="basic-settings">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$username}}
						{{include file="field_input.tpl" field=$email}}
						{{include file="field_password.tpl" field=$password4}}
						{{include file="field_custom.tpl" field=$timezone}}
						{{include file="field_select.tpl" field=$language}}
						{{include file="field_input.tpl" field=$defloc}}
						{{include file="field_checkbox.tpl" field=$allowloc}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The privacity setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="privacy-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#privacy-settings-collapse" aria-expanded="false" aria-controls="privacy-settings-collapse">
							{{$h_prv}}
						</button>
					</h2>
				</div>
				<div id="privacy-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="privacy-settings">
					<div class="panel-body">

						<input type="hidden" name="visibility" value="{{$visibility}}" />

						{{include file="field_input.tpl" field=$maxreq}}

						{{$profile_in_dir nofilter}}

						{{include file="field_checkbox.tpl" field=$profile_in_net_dir}}
						{{include file="field_checkbox.tpl" field=$hide_friends}}
						{{include file="field_checkbox.tpl" field=$hide_wall}}
						{{include file="field_checkbox.tpl" field=$unlisted}}
						{{include file="field_checkbox.tpl" field=$accessiblephotos}}
						{{include file="field_checkbox.tpl" field=$blockwall}}
						{{include file="field_checkbox.tpl" field=$blocktags}}
						{{include file="field_checkbox.tpl" field=$unkmail}}
						{{include file="field_input.tpl" field=$cntunkmail}}

						{{$group_select nofilter}}

						<h3>{{$permissions}}</h3>

						{{$aclselect nofilter}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="expire-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#expire-settings-collapse" aria-expanded="false" aria-controls="expire-settings-collapse">
							{{$expire.label}}
						</button>
					</h2>
				</div>
				<div id="expire-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="expire-settings">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$expire.days}}

						{{include file="field_checkbox.tpl" field=$expire.items}}
						{{include file="field_checkbox.tpl" field=$expire.notes}}
						{{include file="field_checkbox.tpl" field=$expire.starred}}
						{{include file="field_checkbox.tpl" field=$expire.network_only}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The notification setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="notification-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#notification-settings-collapse" aria-expanded="false" aria-controls="notification-settings-collapse">
							{{$h_not}}
						</button>
					</h2>
				</div>
				<div id="notification-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="notification-settings">
					<div id="settings-notifications" class="panel-body">

						<div id="settings-notification-desc">{{$lbl_not}}</div>

						<div class="group">
							{{include file="field_intcheckbox.tpl" field=$notify1}}
							{{include file="field_intcheckbox.tpl" field=$notify2}}
							{{include file="field_intcheckbox.tpl" field=$notify3}}
							{{include file="field_intcheckbox.tpl" field=$notify4}}
							{{include file="field_intcheckbox.tpl" field=$notify5}}
							{{include file="field_intcheckbox.tpl" field=$notify6}}
							{{include file="field_intcheckbox.tpl" field=$notify7}}
							{{include file="field_intcheckbox.tpl" field=$notify8}}
						</div>

						<div id="settings-notify-desc">{{$lbl_notify}}</div>

						<div class="group">
							{{include file="field_checkbox.tpl" field=$notify_like}}
							{{include file="field_checkbox.tpl" field=$notify_announce}}
						</div>

						{{include file="field_checkbox.tpl" field=$email_textonly}}
						{{include file="field_checkbox.tpl" field=$detailed_notif}}

						{{include file="field_checkbox.tpl" field=$notify_ignored}}

						{{* commented out because it was commented out in the original template
						<div class="field">
						 <button type="button" onclick="javascript:Notification.requestPermission(function(perm){if(perm === 'granted')alert('{{$desktop_notifications_success_message}}');});">{{$desktop_notifications}}</button>
						 <span class="field_help">{{$desktop_notifications_note}}</span>
						</div>
						*}}

						{{include file="field_checkbox.tpl" field=$desktop_notifications}}
						<script type="text/javascript">
							(function(){
								let $notificationField = $("#div_id_{{$desktop_notifications.0}}");
								let $notificationCheckbox = $("#id_{{$desktop_notifications.0}}");

								if (getNotificationPermission() === 'granted') {
									$notificationCheckbox.prop('checked', true);
								}
								if (getNotificationPermission() === null) {
									$notificationField.hide();
								}

								$notificationCheckbox.on('change', function(e){
									if (Notification.permission === 'granted') {
										localStorage.setItem('notification-permissions', $notificationCheckbox.prop('checked') ? 'granted' : 'denied');
									} else if (Notification.permission === 'denied') {
										localStorage.setItem('notification-permissions', 'denied');

										$notificationCheckbox.prop('checked', false);
									} else if (Notification.permission === 'default') {
										Notification.requestPermission(function(choice) {
											if (choice === 'granted') {
												localStorage.setItem('notification-permissions', $notificationCheckbox.prop('checked') ? 'granted' : 'denied');
											} else {
												localStorage.setItem('notification-permissions', 'denied');
												$notificationCheckbox.prop('checked', false);
											}
										});
									}
								})
							})();
						</script>
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The additional account setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="additional-account-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#additional-account-settings-collapse" aria-expanded="false" aria-controls="additional-account-settings-collapse">
							{{$h_advn}}
						</button>
					</h2>
				</div>
				<div id="additional-account-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="additional-account-settings">
					<div class="panel-body">
						<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

						{{$pagetype nofilter}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* Import contacts CSV *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="importcontact-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#importcontact-settings-collapse" aria-expanded="false" aria-controls="importcontact-settings-collapse">
							{{$importcontact}}
						</button>
					</h2>
				</div>
				<div id="importcontact-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="importcontact-settings">
					<div class="panel-body">
						<div id="importcontact-relocate-desc">{{$importcontact_text}}</div>
						<input type="hidden" name="MAX_FILE_SIZE" value="{{$importcontact_maxsize}}" />
						<input type="file" name="importcontact-filename" />
					</div>
					<div class="panel-footer">
						<button type="submit" name="importcontact-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The relocate setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="relocate-settings">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#relocate-settings-collapse" aria-expanded="false" aria-controls="relocate-settings-collapse">
							{{$relocate}}
						</button>
					</h2>
				</div>
				<div id="relocate-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="relocate-settings">
					<div class="panel-body">
						<div id="settings-relocate-desc">{{$relocate_text}}</div>
					</div>
					<div class="panel-footer">
						<button type="submit" name="resend_relocate" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
