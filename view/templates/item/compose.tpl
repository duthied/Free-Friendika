<div class="generic-page-wrapper">
	<h2>{{$compose_title}}</h2>
	<div id="profile-jot-wrapper">
		<form class="comment-edit-form" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="compose/{{$type}}" method="post">
		    {{*<!--<input type="hidden" name="return" value="{{$return_path}}" />-->*}}
			<input type="hidden" name="post_id_random" value="{{$rand_num}}" />
			<input type="hidden" name="post_type" value="{{$posttype}}" />
			<input type="hidden" name="wall" value="{{$wall}}" />

			<div id="jot-title-wrap">
				<input type="text" name="title" id="jot-title" class="jothidden jotforms form-control" placeholder="{{$placeholdertitle}}" title="{{$placeholdertitle}}" value="{{$title}}" tabindex="1"/>
			</div>
		    {{if $placeholdercategory}}
				<div id="jot-category-wrap">
					<input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$placeholdercategory}}" title="{{$placeholdercategory}}" value="{{$category}}" tabindex="2"/>
				</div>
		    {{/if}}

			<p class="comment-edit-bb-{{$id}} comment-icon-list">
				<span>
					<button type="button" class="btn btn-sm icon bb-img" aria-label="{{$edimg}}" title="{{$edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="7">
						<i class="fa fa-picture-o"></i>
					</button>
					<button type="button" class="btn btn-sm icon bb-attach" aria-label="{{$edattach}}" title="{{$edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$prompttext}}');" tabindex="8">
						<i class="fa fa-paperclip"></i>
					</button>
				</span>
				<span>
					<button type="button" class="btn btn-sm icon bb-url" aria-label="{{$edurl}}" title="{{$edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="9">
						<i class="fa fa-link"></i>
					</button>
					<button type="button" class="btn btn-sm icon underline" aria-label="{{$eduline}}" title="{{$eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="10">
						<i class="fa fa-underline"></i>
					</button>
					<button type="button" class="btn btn-sm icon italic" aria-label="{{$editalic}}" title="{{$editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="11">
						<i class="fa fa-italic"></i>
					</button>
					<button type="button" class="btn btn-sm icon bold" aria-label="{{$edbold}}" title="{{$edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="12">
						<i class="fa fa-bold"></i>
					</button>
					<button type="button" class="btn btn-sm icon quote" aria-label="{{$edquote}}" title="{{$edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="13">
						<i class="fa fa-quote-left"></i>
					</button>
				</span>
			</p>
			<p>
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text form-control text-autosize" name="body" placeholder="{{$default}}" rows="7" tabindex="3">{{$body}}</textarea>
			</p>

			<p class="comment-edit-submit-wrapper">
{{if $type == 'post'}}
				<span role="presentation" class="form-inline">
					<input type="text" name="location" class="form-control" id="jot-location" value="{{$location}}" placeholder="{{$location_set}}"/>
					<button type="button" class="btn btn-sm icon" id="profile-location"
					        data-title-set="{{$location_set}}"
					        data-title-disabled="{{$location_disabled}}"
					        data-title-unavailable="{{$location_unavailable}}"
					        data-title-clear="{{$location_clear}}"
					        title="{{$location_set}}"
					        tabindex="6">
						<i class="fa fa-map-marker" aria-hidden="true"></i>
					</button>
				</span>
{{/if}}
				<span role="presentation" id="profile-rotator-wrapper">
					<img role="presentation" id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
				</span>
				<span role="presentation" id="character-counter" class="grey text-info"></span>
		        {{if $preview}}
					<button type="button" class="btn btn-defaul btn-sm" onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" tabindex="5"><i class="fa fa-eye"></i> {{$preview}}</button>
		        {{/if}}
				<button type="submit" class="btn btn-primary btn-sm" id="comment-edit-submit-{{$id}}" name="submit" tabindex="4"><i class="fa fa-envelope"></i> {{$submit}}</button>
			</p>

			<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>

{{if $type == 'post'}}
			<h3>{{$visibility_title}}</h3>
			{{$acl_selector nofilter}}

			<div class="jotplugins">
				{{$jotplugins nofilter}}
			</div>
{{else}}
			<input type="hidden" name="group_allow" value="{{$group_allow}}"/>
			<input type="hidden" name="contact_allow" value="{{$contact_allow}}"/>
			<input type="hidden" name="group_deny" value="{{$group_deny}}"/>
			<input type="hidden" name="contact_deny" value="{{$contact_deny}}"/>
{{/if}}
		</form>
	</div>
</div>