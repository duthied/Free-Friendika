<h1>{{$ptitle}}</h1>

{{$nickname_block nofilter}}

<form action="settings" id="settings-form" method="post" autocomplete="off" enctype="multipart/form-data" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<h3 class="settings-heading"><a href="javascript:;">{{$h_pass}}</a></h3>
<div class="settings-content-block">
{{include file="field_password.tpl" field=$password1}}
{{include file="field_password.tpl" field=$password2}}
{{include file="field_password.tpl" field=$password3}}

{{if $oid_enable}}
{{include file="field_input.tpl" field=$openid}}
{{/if}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
</div>

<h3 class="settings-heading"><a href="javascript:;">{{$h_basic}}</a></h3>
<div class="settings-content-block">

{{include file="field_input.tpl" field=$username}}
{{include file="field_input.tpl" field=$email}}
{{include file="field_password.tpl" field=$password4}}
{{include file="field_custom.tpl" field=$timezone}}
{{include file="field_select.tpl" field=$language}}
{{include file="field_input.tpl" field=$defloc}}
{{include file="field_checkbox.tpl" field=$allowloc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
</div>


<h3 class="settings-heading"><a href="javascript:;">{{$h_prv}}</a></h3>
<div class="settings-content-block">

<input type="hidden" name="visibility" value="{{$visibility}}" />

{{include file="field_input.tpl" field=$maxreq}}

{{$profile_in_dir nofilter}}

{{$profile_in_net_dir nofilter}}

{{$hide_friends nofilter}}

{{$hide_wall nofilter}}

{{$blockwall nofilter}}

{{$blocktags nofilter}}

{{$suggestme nofilter}}

{{$unkmail nofilter}}


{{include file="field_input.tpl" field=$cntunkmail}}

{{include file="field_input.tpl" field=$expire.days}}


<div class="field input">
	<span class="field_help"><a href="#advanced-expire-popup" id="advanced-expire" class='popupbox' title="{{$expire.advanced}}">{{$expire.label}}</a></span>
	<div style="display: none;">
		<div id="advanced-expire-popup" style="width:auto;height:auto;overflow:auto;">
			<h3>{{$expire.advanced}}</h3>
			{{include file="field_yesno.tpl" field=$expire.items}}
			{{include file="field_yesno.tpl" field=$expire.notes}}
			{{include file="field_yesno.tpl" field=$expire.starred}}
			{{include file="field_yesno.tpl" field=$expire.network_only}}
		</div>
	</div>

</div>


<div id="settings-default-perms" class="settings-default-perms" >
	<a href="#profile-jot-acl-wrapper" id="settings-default-perms-menu" class='popupbox'>{{$permissions}} {{$permdesc}}</a>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none; margin-bottom: 20px" >

	<div style="display: none;">
		<div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
			{{$aclselect nofilter}}
		</div>
	</div>

	</div>
</div>
<br/>
<div id="settings-default-perms-end"></div>

{{$group_select nofilter}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
</div>




<h3 class="settings-heading"><a href="javascript:;">{{$h_not}}</a></h3>
<div class="settings-content-block">
<div id="settings-notifications">

<div id="settings-notify-desc">{{$lbl_not}}</div>

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

{{include file="field_checkbox.tpl" field=$email_textonly}}
{{include file="field_checkbox.tpl" field=$detailed_notif}}

{{include file="field_yesno.tpl" field=$desktop_notifications}}
<script>
(function(){
    var elm = $("#id_{{$desktop_notifications.0}}_onoff");
    var ckbox = $("#id_{{$desktop_notifications.0}}");

    if (getNotificationPermission() === 'granted') {
        ckbox.val(1);
        elm.find(".off").addClass("hidden");
        elm.find(".on").removeClass("hidden");
    }
	if (getNotificationPermission() === null) {
		elm.parent(".field.yesno").hide();
	}

    $("#id_{{$desktop_notifications.0}}_onoff").on("click", function(e){

        if (Notification.permission === 'granted') {
            localStorage.setItem('notification-permissions', ckbox.val()==1 ? 'granted' : 'denied');
        } else if (Notification.permission === 'denied') {
            localStorage.setItem('notification-permissions', 'denied');

            ckbox.val(0);
            elm.find(".on").addClass("hidden");
            elm.find(".off").removeClass("hidden");

        } else if (Notification.permission === 'default') {
            Notification.requestPermission(function(choice) {
                if (choice === 'granted') {
                    localStorage.setItem('notification-permissions', ckbox.val()==1 ? 'granted' : 'denied');

                } else {
                    localStorage.setItem('notification-permissions', 'denied');
                    ckbox.val(0);
                    elm.find(".on").addClass("hidden");
                    elm.find(".off").removeClass("hidden");
                }
            });
        }

		//console.log(getNotificationPermission());


    })
})();
</script>

</div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
</div>


<h3 class="settings-heading"><a href="javascript:;">{{$h_advn}}</a></h3>
<div class="settings-content-block">
<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

{{$pagetype nofilter}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
</div>

<h3 class="settings-heading"><a href="javascript:;">{{$importcontact}}</a></h3>
<div class="settings-content-block">
<input type="hidden" name="MAX_FILE_SIZE" value="{{$importcontact_maxsize}}" />
<div id="settings-pagetype-desc">{{$importcontact_text}}</div>
<input type="file" name="importcontact-filename" />

<div class="settings-submit-wrapper" >
<input type="submit" name="importcontact-submit" class="importcontact-submit" value="{{$importcontact_button}}" />
</div>
</div>

<h3 class="settings-heading"><a href="javascript:;">{{$relocate}}</a></h3>
<div class="settings-content-block">
<div id="settings-pagetype-desc">{{$relocate_text}}</div>

<div class="settings-submit-wrapper" >
<input type="submit" name="resend_relocate" class="settings-submit" value="{{$relocate_button}}" />
</div>
</div>



