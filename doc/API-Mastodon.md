# Mastodon API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following endpoints defined in [the official Mastodon API reference](https://docs.joinmastodon.org/api/).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Entities

These endpoints use the [Mastodon API entities](https://docs.joinmastodon.org/entities/).

## Implemented endpoints

- [`GET /api/v1//accounts/:id`](https://docs.joinmastodon.org/methods/accounts/#retrieve-information)
- [`GET /api/v1//accounts/:id/statuses`](https://docs.joinmastodon.org/methods/accounts/#retrieve-information)
- [`GET /api/v1/custom_emojis`](https://docs.joinmastodon.org/methods/instance/custom_emojis/)
    - Doesn't return unicode emojis since they aren't using an image URL


- [`GET /api/v1/directory`](https://docs.joinmastodon.org/methods/instance/directory/)
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
- [`GET /api/v1/timelines/public`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/trends`](https://docs.joinmastodon.org/methods/instance/trends/)

## Non-implemented endpoints

- [`GET /api/v1/instance/activity`](https://docs.joinmastodon.org/methods/instance#weekly-activity)
