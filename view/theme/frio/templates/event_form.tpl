
<h3 class="heading">{{$title}}</h3>

{{* The event edit navigation menu (text input, permissions, preview, filebrowser) *}}
<ul id="event-nav" class="nav nav-tabs hidden-xs event-nav" role="menubar" data-tabs="tabs">
	{{* Mark the first list entry as active because it is the first which is active after opening
		the modal. Changing of the activity status is done by js in event_head.tpl *}}
	<li class="active" role="menuitem"><a id="event-edit-lnk" onclick="eventEditActive(); return false;">Text</a></li>
	{{if $acl}}<li role="menuitem" {{if !$sh_checked}} style="display: none"{{/if}}><a id="event-perms-lnk" onclick="eventAclActive();return false;">Permissions</a></li>{{/if}}
	{{if $preview}}<li role="menuitem"><a id="event-preview-lnk" onclick="eventPreviewActive();return false;">{{$preview}}</a></li>{{/if}}
	<li role="menuitem"><a id="event-preview-link" onclick="fbrowserActive(); return false;"> Browser </a></li>
</ul>

<form id="event-edit-form" action="{{$post}}" method="post" >

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="cid" value="{{$cid}}" />
	<input type="hidden" name="uri" value="{{$uri}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />

	<div id="event-edit-wrapper">
		<p>
		{{$desc}}
		</p>

		{{$s_dsel}}

		{{$f_dsel}}

		{{include file="field_checkbox.tpl" field=$nofinish}}

		{{include file="field_checkbox.tpl" field=$adjust}}

		{{include file="field_input.tpl" field=$summary}}


		<div class="form-group">
			<div id="event-desc-text"><b>{{$d_text}}</b></div>
			<textarea id="comment-edit-text-desc" class="form-control" name="desc" >{{$d_orig}}</textarea>
			<ul id="event-desc-text-edit-bb" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
				<li>
					<a class="icon" style="cursor: pointer;" title="{{$edimg|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="desc">
						<i class="fa fa-picture-o"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-url" style="cursor: pointer;" title="{{$edurl|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="desc">
						<i class="fa fa-link"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-video" style="cursor: pointer;" title="{{$edvideo|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="desc">
						<i class="fa fa-video-camera"></i>
					</a>
				</li>

				<li>
					<a class="icon underline" style="cursor: pointer;" title="{{$eduline|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="desc">
						<i class="fa fa-underline"></i>
					</a>
				</li>
				<li>
					<a class="icon italic" style="cursor: pointer;" title="{{$editalic|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="desc">
						<i class="fa fa-italic"></i>
					</a>
				</li>
				<li>
					<a class="icon bold" style="cursor: pointer;"  title="{{$edbold|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="desc">
						<i class="fa fa-bold"></i>
					</a>
				</li>
				<li>
					<a class="icon quote" style="cursor: pointer;" title="{{$edquote|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="desc">
						<i class="fa fa-quote-left"></i>
					</a>
				</li>
			</ul>
			<div class="clear"></div>
		</div>

		<div class="form-group">
			<div id="event-location-text"><b>{{$l_text}}</b></div>
			<textarea id="comment-edit-text-loc" class="form-control" name="location">{{$l_orig}}</textarea>
			<ul id="comment-tools-loc" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
				<li>
					<a class="icon" style="cursor: pointer;" title="{{$edimg|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="loc">
						<i class="fa fa-picture-o"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-url" style="cursor: pointer;" title="{{$edurl|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="loc">
						<i class="fa fa-link"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-video" style="cursor: pointer;" title="{{$edvideo|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="loc">
						<i class="fa fa-video-camera"></i>
					</a>
				</li>

				<li>
					<a class="icon underline" style="cursor: pointer;" title="{{$eduline|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="loc">
						<i class="fa fa-underline"></i>
					</a>
				</li>
				<li>
					<a class="icon italic" style="cursor: pointer;" title="{{$editalic|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="loc">
						<i class="fa fa-italic"></i>
					</a>
				</li>
				<li>
					<a class="icon bold" style="cursor: pointer;"  title="{{$edbold|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="loc">
						<i class="fa fa-bold"></i>
					</a>
				</li>
				<li>
					<a class="icon quote" style="cursor: pointer;" title="{{$edquote|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="loc">
						<i class="fa fa-quote-left"></i>
					</a>
				</li>
			</ul>
			<div class="clear"></div>
		</div>

		<input type="checkbox" name="share" value="1" id="event-share-checkbox" {{$sh_checked}} /> <div id="event-share-text">{{$sh_text}}</div>
		<div id="event-share-break"></div>

		<input id="event-edit-preview" type="submit" name="preview" value="{{$preview|escape:'html'}}" onclick="doEventPreview(); return false;" />
		<input id="event-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
	</div>

	<div id="event-acl-wrapper" style="display: none">
		{{$acl}}
	</div>

	<div class="clear"></div>

</form>

