{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $image->preview}}
<figure>
	<a data-fancybox="uri-id-{{$image->uriId}}" href="{{$image->url}}">
		<img src="{{$image->preview}}" alt="{{$image->description}}" title="{{$image->description}}" {{if $image->description}}class="has-alt-description"{{else}}class="empty-description"{{/if}} loading="lazy">
	</a>
	{{if $image->description}}
		<button class="alt-text-button" type="button" aria-hidden="true">ALT
			<span class="alt-text-block" dir="auto">
				{{$image->description}}
			</span>
		</button>
	{{/if}}
</figure>
{{else}}
<figure>
	<img src="{{$image->url}}" alt="{{$image->description}}" title="{{$image->description}}" {{if $image->description}}class="has-alt-description"{{else}}class="empty-description"{{/if}} loading="lazy">
	{{if $image->description}}
	<figcaption>{{$image->description}}</figcaption>
    {{/if}}
</figure>
{{/if}}
