<div id="live-photos"></div>
<h3 id="photo-album-title"><a href="{{$album.0}}">{{$album.1}}</a></h3>

<div id="photo-edit-link-wrap">
{{if $tools}}
    {{if $tools.view}}
        <a id="photo-view-link" href="{{$tools.view.0}}">{{$tools.view.1}}</a>
    {{/if}}
    {{if $tools.edit}}
        <a id="photo-edit-link" href="{{$tools.edit.0}}">{{$tools.edit.1}}</a>
    {{/if}}
    {{if $tools.delete}}
        | <a id="photo-edit-link" href="{{$tools.delete.0}}">{{$tools.delete.1}}</a>
    {{/if}}
    {{if $tools.profile}}
        | <a id="photo-toprofile-link" href="{{$tools.profile.0}}">{{$tools.profile.1}}</a>
    {{/if}}
    {{if $tools.lock}}
        | <img src="images/lock_icon.gif" class="lockview" alt="{{$tools.lock}}" onclick="lockview(event, 'photo', {{$id}});" />
    {{/if}}
{{/if}}
</div>

<div id="photo-photo"><a href="{{$photo.href}}" title="{{$photo.title}}"><img src="{{$photo.src}}" /></a></div>
{{if $prevlink}}<div id="photo-prev-link"><a href="{{$prevlink.0}}">{{$prevlink.1 nofilter}}</a></div>{{/if}}
{{if $nextlink}}<div id="photo-next-link"><a href="{{$nextlink.0}}">{{$nextlink.1 nofilter}}</a></div>{{/if}}
<div id="photo-caption">{{$desc nofilter}}</div>
{{if $tags}}
<div id="in-this-photo-text">{{$tags.0}}</div>
<div id="in-this-photo">{{$tags.1}}</div>
{{/if}}
{{if $tags.2}}<div id="tag-remove"><a href="{{$tags.2}}">{{$tags.3}}</a></div>{{/if}}

{{if $edit}}{{$edit nofilter}}{{/if}}

{{if $likebuttons}}
<div id="photo-like-div">
	{{$likebuttons nofilter}}
	{{$like nofilter}}
	{{$dislike nofilter}}
</div>
{{/if}}

<div class="wall-item-comment-wrapper photo">
    {{$comments nofilter}}
</div>

{{$paginate nofilter}}

