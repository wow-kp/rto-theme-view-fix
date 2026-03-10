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