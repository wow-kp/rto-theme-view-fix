# Theme Helper — Refactor Notes

## Problem

In our multisite setup, different domains don't share the same set of views. When a page wasn't available for a given domain/theme, Laravel would throw:

```
View [] not found.
```

The `theme()` helper was already correctly returning `null` when no view was found. However, call sites were not handling the `null` case:

```php
// If theme() returns null, view(null) throws: View [] not found
return view(theme('content.show'));
```

## Solution

Rather than updating every call site across the codebase, `theme()` was updated to call `abort(404)` instead of returning `null`, so all existing `view(theme(...))` calls benefit automatically without any further changes.

## Changes

**File:** `app/helpers.php` (or wherever `theme()` is defined)

### Before

```php
if (!function_exists('theme')) {
    /**
     * @param $page
     * @return ?string
     *
     * @phpstan-return ?view-string
     */
    function theme($page): ?string
    {
        $account = getAccount();
        if (session('theme') == '' || session('theme') != $account?->getThemeAttribute()) {
            $theme_name = $account?->getThemeAttribute();
            session(['theme' => $theme_name]);
        }

        // first we look for the file in frontend.themes.{account_id}.{page}
        $view = 'frontend.themes.' . $account?->id . '.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        // then we look for the file in frontend.themes.{theme_slug}.{page}
        $theme_name = session('theme');

        $theme = 'frontend.themes.' . $theme_name;

        $view = $theme . '.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        // last we fall back to the default theme
        $view = 'frontend.themes.default.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        return null;
    }
}
```

### After

```php
if (!function_exists('theme')) {
    /**
     * @param $page
     * @return string
     *
     * @phpstan-return view-string
     */
    function theme($page): string
    {
        $account = getAccount();
        if (session('theme') == '' || session('theme') != $account?->getThemeAttribute()) {
            $theme_name = $account?->getThemeAttribute();
            session(['theme' => $theme_name]);
        }

        // first we look for the file in frontend.themes.{account_id}.{page}
        $view = 'frontend.themes.' . $account?->id . '.' . $page;
        if (view()->exists($view)) {
            return $view;
        }

        // then we look for the file in frontend.themes.{theme_slug}.{page}
        $theme_name = session('theme');
        $view = 'frontend.themes.' . $theme_name . '.' . $page;
        if (view()->exists($view)) {
            return $view;
        }

        // last we fall back to the default theme
        $view = 'frontend.themes.default.' . $page;
        if (view()->exists($view)) {
            return $view;
        }

        // nothing found anywhere — throw a proper 404
        abort(404);
    }
}
```

## What Changed

| | Before | After |
|---|---|---|
| Return type | `?string` | `string` |
| PHPStan annotation | `?view-string` | `view-string` |
| No view found | `return null` | `abort(404)` |
| Call sites | Required `?? fallback` handling | No changes needed |

## View Resolution Order

The helper resolves views in the following priority order:

1. `frontend.themes.{account_id}.{page}` — account-specific override
2. `frontend.themes.{theme_slug}.{page}` — theme-specific view
3. `frontend.themes.default.{page}` — default theme fallback
4. `abort(404)` — no view found anywhere

## Why `abort(404)` Instead of `return null`

- **No call site changes needed** — all existing `view(theme(...))` calls work without modification.
- **Proper HTTP 404 response** — correct status code for SEO and API clients.
- **Clearer intent** — a missing page is an explicit 404, not a null state to be handled by callers.

---

## Optional Further Improvements

### Themed Error Pages

Since the app is multisite, error pages (404, 500, etc.) can also be themed per account/domain. Override `renderHttpException()` in `app/Exceptions/Handler.php` to resolve error views through the same priority chain as `theme()`:

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        $status = $e->getStatusCode();

        $account = getAccount();
        $candidates = [
            'frontend.themes.' . $account?->id . ".errors.{$status}",
            'frontend.themes.' . session('theme') . ".errors.{$status}",
            'frontend.themes.default.' . "errors.{$status}",
            "errors.{$status}",
        ];

        foreach ($candidates as $view) {
            if (view()->exists($view)) {
                return response()->view($view, ['exception' => $e], $status);
            }
        }

        return parent::renderHttpException($e);
    }
}
```

> **Note:** We intentionally avoid calling `theme()` here to prevent infinite recursion — since `theme()` itself calls `abort(404)`, using it inside the exception handler would cause an infinite loop. Instead, we resolve the view candidates manually with `view()->exists()`.

> **Laravel 11:** `Handler.php` may not exist by default as it was consolidated into `bootstrap/app.php`. The equivalent approach differs slightly in that version.

#### Error View Structure

Theme-specific error views should be placed following the same directory convention:

```
resources/views/
  frontend/
    themes/
      default/
        errors/
          404.blade.php
          500.blade.php
      {theme_slug}/
        errors/
          404.blade.php
      {account_id}/
        errors/
          404.blade.php
  errors/          ← Laravel default fallback
    404.blade.php
```

#### Error View Resolution Order

1. `frontend.themes.{account_id}.errors.{status}` — account-specific error view
2. `frontend.themes.{theme_slug}.errors.{status}` — theme-specific error view
3. `frontend.themes.default.errors.{status}` — default theme error view
4. `errors.{status}` — Laravel's built-in error view
5. `parent::renderHttpException()` — Laravel's final fallback handler