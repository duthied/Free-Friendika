
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
{{if $item.thread_level==1}}
<span class="commented" style="display: none;">{{$item.commented}}</span>
<span class="received" style="display: none;">{{$item.received}}</span>
<span class="created" style="display: none;">{{$item.created_date}}</span>
<span class="id" style="display: none;">{{$item.id}}</span>
{{/if}}
<div id="tread-wrapper-{{$item.id}}" class="tread-wrapper {{$item.toplevel}} {{if $item.toplevel}} h-entry {{else}} u-comment h-cite {{/if}}">
<a name="{{$item.id}}" ></a>
<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-outside-wrapper-{{$item.id}}" >
	<div class="wall-item-content-wrapper {{$item.indent}} {{$item.shiny}}" id="wall-item-content-wrapper-{{$item.id}}" >
		<div class="wall-item-info{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-info-{{$item.id}}">
			{{if $item.owner_url}}
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
				<img src="{{$item.owner_photo}}" class="wall-item-photo{{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.owner_name}}" /></a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="{{$item.wall}}" /></div>
			{{/if}}
			<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}} p-author h-card" id="wall-item-photo-wrapper-{{$item.id}}"
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
                onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-photo-link u-url" id="wall-item-photo-link-{{$item.id}}">
				<img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}} u-photo p-name" id="wall-item-photo-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.name}}" /></a>
				<span onclick="openClose('wall-item-photo-menu-{{$item.id}}');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-{{$item.id}}">menu</span>
                <div class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">
                    <ul>
                        {{$item.item_photo_menu nofilter}}
                    </ul>
                </div>

			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-wrapper" id="wall-item-wrapper-{{$item.id}}" >
				{{if $item.lock}}<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" /></div>
				{{else}}<div class="wall-item-lock"></div>{{/if}}
				<div class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location nofilter}}</div>
			</div>
		</div>
		<div class="wall-item-author">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}} {{$item.to}} <a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a> {{$item.vwall}}{{/if}}<br />
				<div class="wall-item-ago" id="wall-item-ago-{{$item.id}}" title="{{$item.localtime}}"><time class="dt-published" datetime="{{$item.localtime}}">{{$item.ago}}</time><span class="pinned">{{$item.pinned}}</span></div>
		</div>
		<div class="wall-item-content" id="wall-item-content-{{$item.id}}" >
			<div class="wall-item-title p-name" id="wall-item-title-{{$item.id}}">{{$item.title}}</div>
			<div class="wall-item-title-end"></div>
			<div class="wall-item-body" id="wall-item-body-{{$item.id}}" ><span class="e-content">{{$item.body nofilter}}<span>
			<div class="body-tag">
			{{if !$item.suppress_tags}}
				{{foreach $item.tags as $tag}}
				<span class="tag">{{$tag nofilter}}</span>
				{{/foreach}}
			{{/if}}
			</div>
			{{if $item.has_cats}}
			<div class="categorytags"><span>{{$item.txt_cats}} {{foreach $item.categories as $cat}}<span class="p-category"><a href="{{$cat.url}}">{{$cat.name}}</a></span>{{if $cat.removeurl}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a>{{/if}} {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}

			{{if $item.has_folders}}
			<div class="filesavetags"><span>{{$item.txt_folders}} {{foreach $item.folders as $cat}}<span class="p-category">{{$cat.name}}</span>{{if $cat.removeurl}} <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a>{{/if}}{{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}
			</div>
		</div>
		<div class="wall-item-tools" id="wall-item-tools-{{$item.id}}">
			{{if $item.vote}}
			<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$item.id}}">
				<a href="#" class="icon like{{if $item.responses.like.self}} active{{/if}}" title="{{$item.vote.like.0}}" onclick="dolike({{$item.id}}, 'like'{{if $item.responses.like.self}}, true{{/if}}); return false"></a>
				{{if $item.vote.dislike}}<a href="#" class="icon dislike{{if $item.responses.dislike.self}} active{{/if}}" title="{{$item.vote.dislike.0}}" onclick="dolike({{$item.id}},'dislike'{{if $item.responses.dislike.self}}, true{{/if}}); return false"></a>{{/if}}
				{{if $item.vote.share}}<a href="#" class="icon recycle wall-item-share-buttons" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false"></a>{{/if}}
				<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
			</div>
			{{/if}}
			{{if $item.remote_comment}}
				<div class="wall-item-links-wrapper"><a href="{{$item.remote_comment.2}}" title="{{$item.remote_comment.0}}" target="_blank" rel="noopener noreferrer" class="icon remote-link{{$item.sparkle}} u-url"></a></div>
			{{/if}}
			{{if $item.plink}}
				<div class="wall-item-links-wrapper"><a href="{{$item.plink.href}}" title="{{$item.plink.title}}" target="_blank" rel="noopener noreferrer" class="icon remote-link{{$item.sparkle}} u-url"></a></div>
			{{/if}}
			{{if $item.edpost}}
				<a class="editpost icon pencil" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
			{{/if}}

			{{if $item.pin}}
			<a href="#" id="pinned-{{$item.id}}" onclick="dopin({{$item.id}}); return false;" class="pin-item icon {{$item.ispinned}}" title="{{$item.pin.toggle}}"></a>
			{{/if}}
			{{if $item.star}}
			<a href="#" id="starred-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="star-item icon {{$item.isstarred}}" title="{{$item.star.toggle}}"></a>
			{{/if}}
			{{if $item.tagger}}
			<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="tag-item icon tagged" title="{{$item.tagger.add}}"></a>
			{{/if}}
			{{if $item.filer}}
			<a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon" title="{{$item.filer}}"></a>
			{{/if}}
			{{if $item.isevent }}
			<div class="wall-item-attend-wrapper">
				<a href="#" id="attendyes-{{$item.id}}" class="icon attendyes{{if $item.responses.attendyes.self}} active{{/if}}" onclick="dolike({{$item.id}}, 'attendyes'{{if $item.responses.attendyes.self}}, true{{/if}}); return false;" title="{{$item.attend.0}}"></a>
				<a href="#" id="attendno-{{$item.id}}" class="icon attendno{{if $item.responses.attendno.self}} active{{/if}}"  onclick="dolike({{$item.id}}, 'attendno'{{if $item.responses.attendno.self}}, true{{/if}}); return false;" title="{{$item.attend.1}}"></a>
				<a href="#" id="attendmaybe-{{$item.id}}"  class="icon attendmaybe{{if $item.responses.attendmaybe.self}} active{{/if}}" onclick="dolike({{$item.id}}, 'attendmaybe'{{if $item.responses.attendmaybe.self}}, true{{/if}}); return false;" title="{{$item.attend.2}}"></a>
			</div>
			{{/if}}
			<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-{{$item.id}}" >
				{{if $item.drop.dropping}}<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon drophide" title="{{$item.drop.delete}}" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a>{{/if}}
			</div>
				{{if $item.drop.pagedrop}}<input type="checkbox" onclick="checkboxhighlight(this);" title="{{$item.drop.select}}" class="item-select" name="itemselected[]" value="{{$item.id}}" />{{/if}}
			<div class="wall-item-delete-end"></div>
		</div>
	</div>
	<div class="wall-item-wrapper-end"></div>
	{{if $item.responses}}
		{{foreach $item.responses as $verb=>$response}}
			<div class="wall-item-{{$verb}} {{$item.indent}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output nofilter}}</div>
		{{/foreach}}
	{{/if}}
			{{if $item.threaded}}
			{{if $item.comment}}
			<div class="wall-item-comment-wrapper {{$item.indent}}" >
				{{$item.comment nofilter}}
			</div>
			{{/if}}
			{{/if}}

<div class="wall-item-outside-wrapper-end {{$item.indent}}" ></div>
</div>
{{foreach $item.children as $child}}
	{{include file="{{$child.template}}" item=$child}}
{{/foreach}}

{{if $item.flatten}}
<div class="wall-item-comment-wrapper" >
	{{$item.comment nofilter}}
</div>
{{/if}}
</div>
{{if $item.comment_lastcollapsed}}</div>{{/if}}
