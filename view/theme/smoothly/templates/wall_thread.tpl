
{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer fakelink" onclick="showHideComments({{$item.id}});">
		<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">
			{{$item.num_comments}} - {{$item.show_text}}
		</span>
		<span id="hide-comments-{{$item.id}}" class="hide-comments" style="display: none">
			{{$item.num_comments}} - {{$item.hide_text}}
		</span>
	</div>
	<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}

<div id="tread-wrapper-{{$item.uriid}}" class="tread-wrapper {{$item.toplevel}} {{if $item.toplevel}} h-entry {{else}} u-comment h-cite {{/if}}">
<div class="wall-item-outside-wrapper {{$item.indent}} {{$item.shiny}} wallwall" id="wall-item-outside-wrapper-{{$item.id}}">
<div class="wall-item-content-wrapper {{$item.indent}} {{$item.shiny}}" id="wall-item-content-wrapper-{{$item.id}}">

		<div class="wall-item-info{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-info-{{$item.id}}">
			{{if $item.owner_url}}
			<div class="wall-item-photo-wrapper mframe wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}">
				<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
				<img src="{{$item.owner_photo}}" class="wall-item-photo{{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.owner_name}}" /></a>
			</div>
			<div class="wall-item-arrowphoto-wrapper"><img src="view/theme/smoothly/images/larrow.gif" alt="{{$item.wall}}" /></div>
			{{/if}}
			<div class="wall-item-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}} p-author h-card" id="wall-item-photo-wrapper-{{$item.id}}"
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
                onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link u-url" id="wall-item-photo-link-{{$item.id}}">
				<img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}} p-name u-photo" id="wall-item-photo-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.name}}" /></a>
				<span onclick="openClose('wall-item-photo-menu-{{$item.id}}');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-{{$item.id}}">menu</span>
                <div class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">
                    <ul>
                        {{$item.item_photo_menu_html nofilter}}
                    </ul>
                </div>

			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-location" id="wall-item-location-{{$item.id}}">{{if $item.location_html}}<span class="icon globe"></span>{{$item.location_html nofilter}} {{/if}}</div>
		</div>
		<div class="wall-item-lock-wrapper">
			{{if $item.lock}}
			<div class="wall-item-lock">
			<img src="images/lock_icon.gif" class="lockview" alt="{{$item.lock}}" onclick="lockview(event, 'item', {{$item.id}});" />
			</div>
			{{else}}
			<div class="wall-item-lock"></div>
			{{/if}}
		</div>
		<div class="wall-item-content" id="wall-item-content-{{$item.id}}">
		<div class="wall-item-author">
			<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link">
			<span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}">{{$item.name}}</span>
			</a>
			<div class="wall-item-ago">&bull;</div>
			<div class="wall-item-ago" id="wall-item-ago-{{$item.id}}"><time class="dt-published" title="{{$item.localtime}}" datetime="{{$item.utc}}">{{$item.ago}}</time><span class="pinned">{{$item.pinned}}</span></div>
		</div>

		<div>
		<hr class="line-dots">
		</div>
			<div class="wall-item-title p-name" id="wall-item-title-{{$item.id}}" dir="auto">{{$item.title}}</div>
			<div class="wall-item-title-end"></div>
			<div class="wall-item-body" id="wall-item-body-{{$item.id}}">
				<span class="e-content" dir="auto">{{$item.body_html nofilter}}</span>
				<div class="body-tag">
				{{if !$item.suppress_tags}}
					{{foreach $item.tags as $tag}}
					<span class="tag">{{$tag}}</span>
					{{/foreach}}
				{{/if}}
				</div>

				{{if $item.has_cats}}
				<div class="categorytags"><span>{{$item.txt_cats}} {{foreach $item.categories as $cat}}<span class="p-category"><a href="{{$cat.url}}">{{$cat.name}}</a></span>
				<a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a>
				{{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
				</div>
				{{/if}}

				{{if $item.has_folders}}
				<div class="filesavetags"><span>{{$item.txt_folders}} {{foreach $item.folders as $cat}}<span class="p-category">{{$cat.name}}</span>
				<a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a>
				{{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
				</div>
				{{/if}}
				{{if $item.edited}}
				<div class="itemedited text-muted">{{$item.edited['label']}} (<span title="{{$item.edited['date']}}">{{$item.edited['relative']}}</span>)</div>
				{{/if}}
			</div>
		</div>
		<div class="wall-item-social" id="wall-item-social-{{$item.id}}">

			{{if $item.vote}}
			<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$item.id}}">
				<a href="#" class="icon like{{if $item.responses.like.self}} self{{/if}}" title="{{$item.vote.like.0}}" onclick="doActivityItem({{$item.id}}, 'like'{{if $item.responses.like.self}}, true{{/if}}); return false"></a>
				{{if $item.vote.dislike}}
				<a href="#" class="icon dislike{{if $item.responses.dislike.self}} self{{/if}}" title="{{$item.vote.dislike.0}}" onclick="doActivityItem({{$item.id}}, 'dislike'{{if $item.responses.dislike.self}}, true{{/if}}); return false"></a>
				{{/if}}
				{{if $item.vote.announce}}
				<a href="#" class="icon recycle{{if $item.responses.announce.self}} self{{/if}}" title="{{$item.vote.announce.0}}" onclick="doActivityItem({{$item.id}}, 'announce'{{if $item.responses.announce.self}}, true{{/if}}); return false"></a>
				{{/if}}
				{{if $item.vote.share}}
				<a href="#" class="icon share wall-item-share-buttons" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false"></a>				{{/if}}
				<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
			</div>
			{{/if}}

			{{if $item.plink}}
			<div class="wall-item-links-wrapper">
				<a href="{{$item.plink.href}}" title="{{$item.plink.title}}" target="external-link" class="icon remote-link u-url"></a>
			</div>
			{{/if}}

			{{if $item.pin}}
			<a href="#" id="pinned-{{$item.id}}" onclick="doPin({{$item.id}}); return false;" class="pin-item icon {{$item.ispinned}}" title="{{$item.pin.toggle}}"></a>
			{{/if}}
			{{if $item.star}}
			<a href="#" id="starred-{{$item.id}}" onclick="doStar({{$item.id}}); return false;" class="star-item icon {{$item.isstarred}}" title="{{$item.star.toggle}}"></a>
			{{/if}}
			{{if $item.tagger}}
			<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="tag-item icon tagged" title="{{$item.tagger.add}}"></a>
			{{/if}}

			{{if $item.filer}}
			<a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon" title="{{$item.filer}}"></a>
			{{/if}}

		</div>

		<div class="wall-item-tools" id="wall-item-tools-{{$item.id}}">
			{{if $item.edpost}}
			<a class="editpost icon pencil" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
			{{/if}}

			<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-{{$item.id}}">
				{{if $item.drop && $item.drop.dropping}}
				<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon drophide" title="{{$item.drop.label}}" onmouseover="imgbright(this);" onmouseout="imgdull(this);"></a>
				{{/if}}
			</div>

			{{if $item.drop && $item.drop.pagedrop}}
			<input type="checkbox" onclick="checkboxhighlight(this);" title="{{$item.drop.select}}" class="item-select" name="itemselected[]" value="{{$item.id}}" />
			{{/if}}

			<div class="wall-item-delete-end"></div>
		</div>

	</div>
	<div class="wall-item-wrapper-end"></div>
	<div class="wall-item-like" id="wall-item-like-{{$item.id}}">{{$item.like_html nofilter}}</div>
	<div class="wall-item-dislike" id="wall-item-dislike-{{$item.id}}">{{$item.dislike_html nofilter}}</div>

	{{if $item.threaded}}
	{{if $item.comment_html}}
        <div class="wall-item-comment-wrapper {{$item.indent}} {{$item.shiny}}">
		{{$item.comment_html nofilter}}
	</div>
	{{/if}}
	{{/if}}
</div>

<div class="wall-item-outside-wrapper-end {{$item.indent}} {{$item.shiny}}"></div>

{{foreach $item.children as $child}}
	{{include file="{{$child.template}}" item=$child}}
{{/foreach}}

{{if $item.flatten}}
<div class="wall-item-comment-wrapper">
	{{$item.comment_html nofilter}}
</div>
{{/if}}
</div>
{{if $item.comment_lastcollapsed}}</div>{{/if}}
