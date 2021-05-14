# Mastodon API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following endpoints defined in [the official Mastodon API reference](https://docs.joinmastodon.org/api/).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Clients

Supported mobile apps:

- Tusky
- Husky
- twitlatte
- AndStatus
- Twidere
- Subway Tooter

Unsupported mobile apps:

- [Mammut](https://github.com/jamiesanson/Mammut) There are problems with the token request, see issue https://github.com/jamiesanson/Mammut/issues/19
- [Fedilab](https://framagit.org/tom79/fedilab) Automatically uses the legacy API, see issue: https://framagit.org/tom79/fedilab/-/issues/520

## Entities

These endpoints use the [Mastodon API entities](https://docs.joinmastodon.org/entities/).

## Implemented endpoints

- [`GET /api/v1/accounts/:id`](https://docs.joinmastodon.org/methods/accounts/#retrieve-information)
- [`GET /api/v1/accounts/:id/statuses`](https://docs.joinmastodon.org/methods/accounts/#retrieve-information)
- [`GET /api/v1/accounts/:id/followers`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/following`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/lists`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/search`](https://docs.joinmastodon.org/methods/accounts)
- [`GET /api/v1/accounts/verify_credentials`](https://docs.joinmastodon.org/methods/accounts)
- [`GET /api/v1/blocks`](https://docs.joinmastodon.org/methods/accounts/blocks/)
- [`GET /api/v1/bookmarks`](https://docs.joinmastodon.org/methods/accounts/bookmarks/)
- [`GET /api/v1/custom_emojis`](https://docs.joinmastodon.org/methods/instance/custom_emojis/)
    - Doesn't return unicode emojis since they aren't using an image URL


- [`GET /api/v1/directory`](https://docs.joinmastodon.org/methods/instance/directory/)
- [`GET /api/v1/favourites`](https://docs.joinmastodon.org/methods/accounts/favourites/)
- [`GET /api/v1/follow_requests`](https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows)
    - Returned IDs are specific to follow requests
- [`POST /api/v1/follow_requests/:id/authorize`](https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow)
    - `:id` is a follow request ID, not a regular account id
- [`POST /api/v1/follow_requests/:id/reject`](https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow)
    - `:id` is a follow request ID, not a regular account id
- `POST /api/v1/follow_requests/:id/ignore`
    - Friendica-specific, hides the follow request from the list and prevents the remote contact from retrying.
    - `:id` is a follow request ID, not a regular account id
    - Returns a [Relationship](https://docs.joinmastodon.org/entities/relationship) object.


- [`GET /api/v1/instance`](https://docs.joinmastodon.org/methods/instance#fetch-instance)
- [`GET /api/v1/instance/peers`](https://docs.joinmastodon.org/methods/instance#list-of-connected-domains)
- [`GET /api/v1/lists`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`GET /api/v1/lists/:id`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`GET /api/v1/lists/:id/accounts`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`GET /api/v1/media/:id`](https://docs.joinmastodon.org/methods/statuses/media/)
- [`GET /api/v1/mutes`](https://docs.joinmastodon.org/methods/accounts/mutes/)
- [`GET /api/v1/notifications`](https://docs.joinmastodon.org/methods/notifications/)
- [`GET /api/v1/notifications/:id`](https://docs.joinmastodon.org/methods/notifications/)
- [`GET /api/v1/preferences`](https://docs.joinmastodon.org/methods/accounts/preferences/)
- [`GET /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/)
- [`GET /api/v1/statuses/:id/context`](https://docs.joinmastodon.org/methods/statuses/)
- [`GET /api/v1/statuses/:id/reblogged_by`](https://docs.joinmastodon.org/methods/statuses/)
- [`GET /api/v1/statuses/:id/favourited_by`](https://docs.joinmastodon.org/methods/statuses/)
- [`GET /api/v1/suggestions`](https://docs.joinmastodon.org/methods/accounts/suggestions/)
- [`GET /api/v1/timelines/home`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/list/:id`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/public`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/tag/:hashtag`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/trends`](https://docs.joinmastodon.org/methods/instance/trends/)

## Non-implemented endpoints

- [`GET /api/v1/instance/activity`](https://docs.joinmastodon.org/methods/instance#weekly-activity)
