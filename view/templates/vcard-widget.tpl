
<div class="vcard">
        <div class="fn">{{$name}}</div>
        {{if $url}}
        <div id="profile-photo-wrapper"><a href="{{$url}}"><img class="vcard-photo photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></a></div>
        {{else}}
        <div id="profile-photo-wrapper"><img class="vcard-photo photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></div>
        {{/if}}
</div>
