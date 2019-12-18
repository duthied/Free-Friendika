# Mastodon API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following endpoints defined in [the official Mastodon API reference](https://docs.joinmastodon.org/api/).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Entities

These endpoints use the [Mastodon API entities](https://docs.joinmastodon.org/api/entities/).

## Implemented endpoints

- [GET /api/v1/follow_requests](https://docs.joinmastodon.org/api/rest/follow-requests/#get-api-v1-follow-requests)
- [POST /api/v1/follow_requests/:id/authorize](https://docs.joinmastodon.org/api/rest/follow-requests/#post-api-v1-follow-requests-id-authorize)
    - Returns a [Relationship](https://docs.joinmastodon.org/api/entities/#relationship) object.
- [POST /api/v1/follow_requests/:id/reject](https://docs.joinmastodon.org/api/rest/follow-requests/#post-api-v1-follow-requests-id-reject)
    - Returns a [Relationship](https://docs.joinmastodon.org/api/entities/#relationship) object.
- POST /api/v1/follow_requests/:id/ignore
    - Friendica-specific, hides the follow request from the list and prevents the remote contact from retrying.
    - Returns a [Relationship](https://docs.joinmastodon.org/api/entities/#relationship) object.
    

- [GET /api/v1/instance](https://docs.joinmastodon.org/api/rest/instances)
- GET /api/v1/instance/peers - undocumented, but implemented by Mastodon and Pleroma



## Non-implemented endpoints
