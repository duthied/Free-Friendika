<script>
	function commentOpenUI(obj, id) {
		$(document).unbind( "click.commentOpen", handler );

		var handler = function() {
			if(obj.value == '{{$comment}}') {
				obj.value = '';
				$("#comment-edit-text-" + id).addClass("comment-edit-text-full").removeClass("comment-edit-text-empty");
				// Choose an arbitrary tab index that's greater than what we're using in jot (3 of them)
				// The submit button gets tabindex + 1
				$("#comment-edit-text-" + id).attr('tabindex','9');
				$("#comment-edit-submit-" + id).attr('tabindex','10');
				$("#comment-edit-submit-wrapper-" + id).show();
			}
		};

		$(document).bind( "click.commentOpen", handler );
}

	function commentCloseUI(obj, id) {
		$(document).unbind( "click.commentClose", handler );

		var handler = function() {
			if(obj.value === '') {
			obj.value = '{{$comment}}';
				$("#comment-edit-text-" + id).removeClass("comment-edit-text-full").addClass("comment-edit-text-empty");
				$("#comment-edit-text-" + id).removeAttr('tabindex');
				$("#comment-edit-submit-" + id).removeAttr('tabindex');
				$("#comment-edit-submit-wrapper-" + id).hide();
			}
		};

		$(document).bind( "click.commentClose", handler );
	}

</script>

{{if $threaded}}
<div class="comment-wwedit-wrapper threaded" id="comment-edit-wrapper-{{$id}}" style="display: block;">
{{else}}
<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
{{/if}}
	<form class="comment-edit-form" style="display: block;" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
		<input type="hidden" name="type" value="{{$type}}" />
		<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
		<input type="hidden" name="parent" value="{{$parent}}" />
		{{*<!--<input type="hidden" name="return" value="{{$return_path}}" />-->*}}
		<input type="hidden" name="jsreload" value="{{$jsreload}}" />
		<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />
		<input type="hidden" name="post_id_random" value="{{$rand_num}}" />

		<!--<div class="comment-edit-photo" id="comment-edit-photo-{{$id}}" >
			<a class="comment-edit-photo-link" href="{{$mylink}}" title="{{$mytitle}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle}}" title="{{$mytitle}}" /></a>
		</div>
		<div class="comment-edit-photo-end"></div>-->
		<div class="bb form-group">
			<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty form-control" name="body" onFocus="commentOpenUI(this,{{$id}});" onBlur="commentCloseUI(this,{{$id}});">{{$comment}}</textarea>
		</div>
		{{if $qcomment}}
			<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});" >
			<option value=""></option>
		{{foreach $qcomment as $qc}}
			<option value="{{$qc}}">{{$qc}}</option>
		{{/foreach}}
			</select>
		{{/if}}

		<div class="comment-edit-text-end"></div>
		<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">
			<button class="btn btn-primary btn-sm" type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" name="submit"><i class="fa fa-envelope"></i> {{$submit}}</button>
			{{if $preview}}
				<button class="btn btn-defaul btn-sm" type="button" onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}"><i class="fa fa-eye"></i> {{$preview}}</button>
			{{/if}}
			<ul class="comment-edit-bb-{{$id}} comment-icon-list nav nav-pills pull-right">
				<li>
					<a class="icon" style="cursor: pointer;" title="{{$edimg}}" data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="img" data-id="{{$id}}">
						<i class="fa fa-picture-o"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-url" style="cursor: pointer;" title="{{$edurl}}" onclick="insertFormatting('{{$comment}}','url',{{$id}});">
						<i class="fa fa-link"></i>
					</a>
				</li>
				<li>
					<a class="icon bb-video" style="cursor: pointer;" title="{{$edvideo}}" onclick="insertFormatting('{{$comment}}','video',{{$id}});">
						<i class="fa fa-video-camera"></i>
					</a>
				</li>

				<li>
					<a class="icon underline" style="cursor: pointer;" title="{{$eduline}}" onclick="insertFormatting('{{$comment}}','u',{{$id}});">
						<i class="fa fa-underline"></i>
					</a>
				</li>
				<li>
					<a class="icon italic" style="cursor: pointer;" title="{{$editalic}}" onclick="insertFormatting('{{$comment}}','i',{{$id}});">
						<i class="fa fa-italic"></i>
					</a>
				</li>
				<li>
					<a class="icon bold" style="cursor: pointer;"  title="{{$edbold}}" onclick="insertFormatting('{{$comment}}','b',{{$id}});">
						<i class="fa fa-bold"></i>
					</a>
				</li>
				<li>
					<a class="icon quote" style="cursor: pointer;" title="{{$edquote}}" onclick="insertFormatting('{{$comment}}','quote',{{$id}});">
						<i class="fa fa-quote-left"></i>
					</a>
				</li>
			</ul>
			<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
		</div>

		<div class="comment-edit-end"></div>
	</form>

</div>
