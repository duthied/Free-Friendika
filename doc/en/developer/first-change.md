# Your First Change in Friendica

* [Developer Intro](index)
* [PHP Architecture Guidelines](php-architecture)
* [Frontend Guidelines](frontend)

This guide walks you through making your first real change to Friendica.

**Who this is for:**
- **New PHP developers** — follow it step by step, the order matters
- **UI/UX contributors** — if you change *only templates or CSS*, Steps 1 and 8 plus the [Frontend Guidelines](frontend) are enough. If you *add or change a form or any Module logic*, read Steps 1–8 in full — input validation (Step 4) and authorization (Step 5) are not optional
- **Experienced Friendica developers** — the canonical module skeleton in Step 3 shows the target pattern for new code; legacy code in `mod/` and `src/Model/` continues to work as-is

---

<a name="step1" id="step1"></a>
## Step 1 — Find the right file

Most user-facing features live in `src/Module/`. The URL maps to the class through
`static/routes.config.php`:

```php
'/profile/{nickname}' => $profileRoutes,
'/settings/server'    => [Module\Settings\Server\Index::class, [R::GET, R::POST]],
```

Routes are often nested under a group (e.g. `/settings` → `/server` → `[Index::class, ...]`), so search `routes.config.php` for the **Module class name** rather than the full URL path.

Route parameters such as `{nickname}` are available as `$this->parameters['nickname']` inside the module.

If you are fixing a visual issue, the template is usually in `view/templates/` with the same path as the module:

```
src/Module/Settings/Server/Index.php   →  view/templates/settings/server/index.tpl
```

---

<a name="lifecycle" id="lifecycle"></a>
## Step 2 — Understand the module lifecycle

Every module extends `BaseModule`.
On every request, `BaseModule::run()` calls methods in this fixed order regardless of HTTP method:

```
1.  method handler   →  get() / post() / put() / patch() / delete()
2.  rawContent()     →  for API/technical endpoints that exit themselves
3.  content()        →  always runs after the method handler
```

**This is the most important thing to understand:** after a POST, `content()` always runs unless the handler redirects, throws, or exits via `rawContent()`.

The normal pattern for a form that changes data:

```php
protected function post(array $request = []): void
{
    // 1. Check login and permissions
    // 2. Verify CSRF token
    // 3. Do the work
    // 4. Redirect — prevents re-POST on browser reload
    $this->baseUrl->redirect($this->args->getQueryString());
}

protected function content(array $request = []): string
{
    // Renders the page for GET, and for POST if post() did not redirect
}
```

---

<a name="skeleton" id="skeleton"></a>
## Step 3 — A canonical module showing the target pattern

Use this as your starting point for new modules.
It shows the target pattern from the [Architecture Guidelines](php-architecture): constructor injection, no superglobals, no direct DB access, explicit auth and CSRF ([Cross-Site Request Forgery](https://owasp.org/www-community/attacks/csrf)) checks, typed input.
Replace the illustrative `WidgetService` with your own before using it.

```php
<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Example;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Example\Service\WidgetService;  // illustrative — your own service class
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Widget extends BaseModule
{
    public function __construct(
        private readonly IHandleUserSessions $session,
        private readonly WidgetService $widgetService,  // your injected business-logic service
        L10n $l10n,
        App\BaseURL $baseUrl,
        App\Arguments $args,
        LoggerInterface $logger,
        Profiler $profiler,
        Response $response,
        array $server,
        array $parameters,
        EventDispatcherInterface $eventDispatcher, // must be explicit — omitting triggers DI:: fallback in BaseModule
    ) {
        parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
    }

    /**
     * Returns the logged-in local user ID or throws. getLocalUserId() returns int|false,
     * so we validate the type once and reuse the result.
     */
    private function requireLocalUserId(): int
    {
        $uid = $this->session->getLocalUserId();
        if (!is_int($uid) || $uid <= 0) {
            throw new HTTPException\UnauthorizedException($this->t('Please login to continue.'));
        }

        return $uid;
    }

    protected function post(array $request = []): void
    {
        $uid = $this->requireLocalUserId();

        self::checkFormSecurityTokenRedirectOnError(
            $this->args->getQueryString(),
            'example-widget'
        );

        // Read directly from the passed $request (NOT a superglobal) so we can type-check.
        // getRequestValue() with a string default would coerce an array like name[]=x to "Array".
        $rawName = $request['name'] ?? null;
        if (!is_string($rawName)) {
            throw new HTTPException\BadRequestException($this->t('Please enter a valid name.'));
        }

        $name = trim($rawName);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new HTTPException\BadRequestException($this->t('Please enter a valid name.'));
        }

        // Delegate to the injected service. The Service holds business logic;
        // the Repository (called by the Service) does the persistence.
        $this->widgetService->updateName($uid, $name);

        $this->baseUrl->redirect($this->args->getQueryString());
    }

    protected function content(array $request = []): string
    {
        $this->requireLocalUserId();

        $tpl = Renderer::getMarkupTemplate('example/widget.tpl');

        return Renderer::replaceMacros($tpl, [
            '$title'               => $this->t('My Widget'),
            '$form_security_token' => self::getFormSecurityToken('example-widget'),
        ]);
    }
}
```

> **This skeleton extends `BaseModule` directly.** If you instead extend `BaseSettings` or `BaseAdmin`, their parent constructor starts with different arguments (`parent::__construct($session, $page, $l10n, …)`) — copy the `parent::__construct(...)` call from an existing subclass of that base, not from here.
>
> **Why `EventDispatcherInterface` is in the constructor:** `BaseModule` falls back to `DI::eventDispatcher()` when the parameter is omitted.
> Injecting it explicitly avoids that hidden service-locator call.
> Dice wires it automatically.
>
> **This Module is not yet a fully isolated unit-test candidate.**
> It still calls the accepted legacy static framework APIs (`Renderer::*`, `self::getFormSecurityToken*()`) which use `DI::` internally.
> The *Service* it delegates to is the part that is cleanly unit-testable.
> A functional test usually covers a Module like this.
>
> **`WidgetService` is illustrative — replace it before using this file.**
> The block is a pattern, not compile-ready code.
> `WidgetService` is a plain injectable service (see the [Architecture Guidelines](php-architecture) for what belongs in a Service vs a Repository).
> Note the method is `updateName()`, not `save()` — a Service exposes intent-named operations; persistence happens in the Repository it calls.

### How the layers fit together

The Module does not contain the business logic — it hands typed values to a Service:

```
HTTP request
     │
     ▼
   Module          auth · CSRF · input type-checking & validation
     │  (typed values)
     ▼
   Service         business decisions
     │
     ▼
Repository / Read Repository
     │
     ▼
 Database
```

A minimal version of the `WidgetService` the Module calls:

```php
final class WidgetService
{
    public function __construct(
        private readonly WidgetRepository $widgets,
    ) {}

    public function updateName(int $uid, string $name): void
    {
        $widget = $this->widgets->selectByUserId($uid);
        $widget->rename($name);
        $this->widgets->save($widget);
    }
}
```

The Service holds the "what should happen" decision; the Repository does the actual database work.
No `DBA::`, no `$_REQUEST`, no HTTP concerns reach this far down.

---

<a name="request" id="request"></a>
## Step 4 — Read request values safely

Never use `$_GET`, `$_POST`, `$_REQUEST`, or `$_SERVER` directly.
Read values from the passed `$request` array.
Use `$this->getRequestValue()` where its coercion semantics fit;
for untrusted strings, read `$request[...]` and check `is_string()` explicitly.

`getRequestValue()` applies **type-dependent filtering** based on the type of the default value.
It is **not** full input validation — it does not check length, allowed values, or business rules, and with a string default it coerces an array input (`name[]=x`) to the literal string `"Array"`.
Validate separately before calling a service or repository.

`getRequestValue()` is fine for simple `int` and `float` fields, and for `bool` fields *only* when treating missing, invalid, and explicit-`false` values identically is acceptable:

```php
$page   = $this->getRequestValue($request, 'page', 0);     // int filter
$active = $this->getRequestValue($request, 'active', false); // bool filter
```

For **strings**, do not use the string-default form — read directly and type-check:

```php
$rawKeyword = $request['q'] ?? null;
if (!is_string($rawKeyword)) {
    throw new HTTPException\BadRequestException($this->t('Invalid input.'));
}
$keyword = trim($rawKeyword);
```

For `int` and `float` defaults without min/max bounds, the filter returns `false` on invalid input — **not** the default.
Always check:

```php
$page = $this->getRequestValue($request, 'page', 0);
if ($page === false) {
    $page = 0;
}
```

Do not use the optional min/max arguments when you need to detect invalid numeric input.
`getRequestValue()` clamps after filtering; an invalid value can become the minimum instead of staying `false`.
Validate first, clamp second.

**Quick reference — how to read each input type safely:**

| Input type      | Recommended approach                                                                                                              |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------|
| String          | `$request[...]` + `is_string()`, then validate length/content                                                                     |
| Integer / Float | `getRequestValue()` with a numeric default, then check for `false`                                                                |
| Boolean         | `getRequestValue()` only when missing/invalid/`false` may be treated the same; otherwise check the raw submitted value explicitly |
| Array           | `is_array()`, then validate every element individually                                                                            |


> For `bool` defaults, `FILTER_VALIDATE_BOOLEAN` returns `false` for both a valid `false` value **and** invalid input.
> You cannot distinguish between them.
> Do not use `getRequestValue()` to detect whether a boolean field was submitted at all.
>
> **`checkDefaults()` logs request data.**
> It logs undeclared field names with their values, and the normalized request at debug level.
> Do not use it for forms containing passwords, tokens, or secrets.


---

<a name="auth" id="auth"></a>
## Step 5 — Check login and permissions

Because `content()` always runs after `post()`, each protected method needs its own auth check — you cannot rely on `content()` to protect `post()`.

**For login-required modules** (as shown in the skeleton):

```php
// Read and type-check the user ID once
$uid = $this->session->getLocalUserId();

// Not logged in → choose ONE of these two approaches:

// (a) Throw 401 — best for API-style endpoints
if (!is_int($uid) || $uid <= 0) {
    throw new HTTPException\UnauthorizedException($this->t('Please login to continue.'));
}

// (b) Redirect to login — best for interactive pages (use instead of the throw above)
// if (!is_int($uid) || $uid <= 0) {
//     $this->baseUrl->redirect('login');
// }
```

Logged in but not permitted is a separate, 403-level check — see **ownership checks** below.

**For admin-only modules** — extend `BaseAdmin`, and call `self::checkAdminAccess()` at the start of every admin-only request handler that reads or mutates protected data.
`BaseAdmin::content()` runs the check, but only when rendering — too late to protect `post()`:

```php
protected function post(array $request = []): void
{
    self::checkAdminAccess();   // ← must be here, not only in content()
    self::checkFormSecurityTokenRedirectOnError(
        $this->args->getQueryString(), 'admin-my-action'
    );
    // mutation, then redirect
}

protected function content(array $request = []): string
{
    parent::content($request);  // runs checkAdminAccess() inside BaseAdmin
    // render
}
```

**For ownership checks** (reuse the `$uid` you already read and type-checked):

```php
// A raw DB-row value may be a numeric string ('42'), so cast at the legacy boundary
// to avoid a strict-comparison mismatch ('42' !== 42 is true).
if ((int) $record['uid'] !== $uid) {
    throw new HTTPException\ForbiddenException();
}

// With a typed Entity, no cast is needed:
// if ($record->uid !== $uid) { ... }
```

---

<a name="csrf" id="csrf"></a>
## Step 6 — Protect POST forms against CSRF

The action name must match between token generation and verification.
The token value itself travels through the hidden field.
The current helpers are accepted legacy static APIs; on failure they read and log `$_REQUEST` internally.

```php
// In content() — generate:
'$form_security_token' => self::getFormSecurityToken('my-action'),
```

```smarty
{{* In the template — hidden field: *}}
<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
```

```php
// In post() — verify (same action name):
self::checkFormSecurityTokenRedirectOnError(
    $this->args->getQueryString(),
    'my-action'
);
```

---

<a name="service" id="service"></a>
## Step 7 — Call a service or repository

Data should come from an injected repository or service.
See the [Architecture Guidelines](php-architecture) for the full layer model.

If no repository exists for what you need, calling an existing legacy `Model\*` method in a focused fix is acceptable —
**do not introduce a new `DBA::` call in a Module or Service**.
If you add a `@todo`, make it concrete:

```php
// @todo Replace LegacyModel::loadByAlias() once a UserRepository lookup exists.
// Tracked in issue #NNNN.
```

A bare `// @todo migrate` is not a justification — explain the legacy decision in your PR description instead.

---

<a name="template" id="template"></a>
## Step 8 — Render a template

```php
protected function content(array $request = []): string
{
    $tpl = Renderer::getMarkupTemplate('mymodule/index.tpl');
    return Renderer::replaceMacros($tpl, [
        '$title'               => $this->t('My Page Title'),
        '$form_security_token' => self::getFormSecurityToken('my-action'),
        '$items'               => $items,
    ]);
}
```

Template variables are HTML-escaped automatically by Smarty in normal HTML text and attribute contexts.
All user-visible strings go through `$this->t()`.
See the [Frontend Guidelines](frontend) for URL validation, JSON-to-JS, `nofilter`, forms, and output safety.

---

<a name="route" id="route"></a>
## Step 9 — Add a route (new feature only)

```php
// In static/routes.config.php:
'/my-path[/{id:\d+}]' => [Module\Example\Widget::class, [R::GET, R::POST]],
```

---

<a name="dice" id="dice"></a>
## Step 10 — Register a Dice dependency (new class only)

Dice resolves concrete classes with typed constructors automatically.
**Interfaces, scalars, and runtime values** need an explicit rule in `static/dependencies.config.php`:

```php
\Friendica\Example\Capability\IDoSomething::class => [
    'instanceOf' => \Friendica\Example\Model\MyImplementation::class,
],
```

---

<a name="checks" id="checks"></a>
## Step 11 — Run the checks

```bash
# 1. Apply automatic fixes (Rector + CS-Fixer + translations). Modifies files.
composer run rectify   # shorthand: bin/rectify.sh

# 2. Review what changed
git diff

# 3. Validate
composer run lint       # parse errors
composer run cs:check   # code style (read-only)
composer run phpstan    # static type analysis
composer run phpmd      # complexity

# 4. Tests
composer run test:unit  # fast, no database needed
# Also run the relevant functional or integration tests for the layer you changed, if they exist
```

Database-dependent tests need a few `MYSQL_*` environment variables — see [Tests](tests) for the full suite list and setup.

---

<a name="checklist" id="checklist"></a>
## Quick checklist before pushing

- [ ] Each protected entry point (get, post, put, patch, delete, rawContent, content) is covered by authentication and authorization — directly or through a verified base-class guard
- [ ] Admin modules call `self::checkAdminAccess()` at the start of every admin-only request handler that reads or mutates protected data
- [ ] CSRF action name matches between `getFormSecurityToken()` and the verify call
- [ ] No `$_GET` / `$_POST` / `$_REQUEST` / `$_SERVER` direct access
- [ ] `getRequestValue()` int/float results checked for `false` before use
- [ ] `checkDefaults()` not used for forms with passwords, tokens, or secrets; it logs undeclared field values and the normalized request
- [ ] No new `DBA::` call in a Module or Service
- [ ] `EventDispatcherInterface` injected in new BaseModule subclasses
- [ ] All user-visible strings go through `$this->t()`
- [ ] `composer run rectify` run and diff reviewed
- [ ] `phpstan` passes with no new errors
- [ ] Relevant functional or integration tests for the changed layer run and pass (if they exist)

---

<a name="examples" id="examples"></a>
## Reference examples — read the caveats

These existing files are useful for specific things only.
Several predate the current guidelines and use `DI::`, `$_REQUEST`, or `DBA::` directly.

| What to study                 | File                                                             | What to take from it                                | What to ignore                                                                                                                                                                        |
|-------------------------------|------------------------------------------------------------------|-----------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Repository injection          | `src/Module/Settings/Server/Index.php`                           | How to inject and call a repository                 | Auth — `post()` has no explicit login check; it relies on `checkFormSecurityToken` redirect + `getLocalUserId()` inside repository calls; see [Step 5](#auth) for the correct pattern |
| Admin access + CSRF sequence  | `src/Module/Admin/Logs/Settings.php`                             | The `checkAdminAccess()` + CSRF + redirect sequence | Uses `DI::` and missing return types                                                                                                                                                  |
| Route and template wiring     | `src/Module/Profile/Schedule.php`                                | How routes map to modules and templates             | Uses `DI::`, `$_REQUEST`, `DBA::` — do not copy request or DB patterns                                                                                                                |
| Entity / Factory / Repository | `src/Navigation/Notifications/`                                  | Modern DDD pattern                                  | Predates the `declare(strict_types=1)` rule ([Architecture §4.4](php-architecture)) — new classes still require it                                                                    |
| Unit test structure           | `tests/Unit/Contact/FriendSuggest/Factory/FriendSuggestTest.php` | Test isolation and data-provider structure          | `dataCreate()` predates the return-type rule                                                                                                                                          |

For request handling, DI, and CSRF, **prefer the skeleton in [Step 3](#skeleton)** over any of these files.
