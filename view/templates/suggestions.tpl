

<div class="intro-wrapper" >

<p class="intro-desc">{{$str_notifytype}} {{$notify_type}}</p>
<div class="intro-madeby">{{$madeby}}</div>
<div class="intro-fullname" >{{$fullname}}</div>
<a class="intro-url-link" href="{{$url}}" ><img class="intro-photo lframe" src="{{$photo}}" width="175" height=175" title="{{$fullname|escape:'html'}}" alt="{{$fullname|escape:'html'}}" /></a>
<div class="intro-note" >{{$note}}</div>
<div class="intro-wrapper-end"></div>
<form class="intro-form" action="notifications/{{$intro_id}}" method="post">
<input class="intro-submit-ignore" type="submit" name="submit" value="{{$ignore|escape:'html'}}" />
<input class="intro-submit-discard" type="submit" name="submit" value="{{$discard|escape:'html'}}" />
</form>
<div class="intro-form-end"></div>

<form class="intro-approve-form" action="{{$request}}" method="get">
{{include file="field_checkbox.tpl" field=$hidden}}
<input class="intro-submit-approve" type="submit" name="submit" value="{{$approve|escape:'html'}}" />
</form>
</div>
<div class="intro-end"></div>
