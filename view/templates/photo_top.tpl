{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="photo-top-image-wrapper lframe" id="photo-top-image-wrapper-{{$photo.id}}">
	<a href="{{$photo.link}}" class="photo-top-photo-link" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title}}">
		<img src="{{$photo.src}}" alt="{{$photo.alt}}" title="{{$photo.title}}" class="photo-top-photo{{$photo.twist}}" id="photo-top-photo-{{$photo.id}}" />
	</a>
	<div class="photo-top-album-name"><a href="{{$photo.album.link}}" class="photo-top-album-link" title="{{$photo.album.alt}}">{{$photo.album.name}}</a></div>
</div>

