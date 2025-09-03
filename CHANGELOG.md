
## [Stage-2] Leaderboard History & Sorting — 2025-09-03

- Added clickable sortable headers in `[bhg_leaderboard]` (Position, Username, Guess).
- Implemented new `[bhg_leaderboard_history]` shortcode:
  - Dropdown of past closed hunts (latest 20).
  - Selecting a hunt shows its leaderboard (reuses existing rendering).


## [Stage-1] Hygiene & Safety — 2025-09-03

- Removed deprecated `includes/db.php` to avoid confusion with legacy ad renderer pointing to a different table name.
- Standardized sanitization of `$_GET` / query args for leaderboard: `orderby`, `order`, `paged`, `hunt_id`.
- Added server-side pagination links to `[bhg_leaderboard]` when total > per_page (uses `paginate_links()`).
- Minor hardening in `[bhg_guess_form]` guest redirect URL construction (sanitizes host/URI).
- Note: For MySQL 5.5.5 in strict mode, `DATETIME` defaults of `0000-00-00 00:00:00` may require disabling NO_ZERO_DATE / NO_ZERO_IN_DATE or adjusting defaults.

# Changelog

## 8.0.05 — 2025-09-03
- Fix: Affiliate Websites edit query querying wrong table (now selects by `id`).
- Security: Server-side enforcement — guesses can only be added/edited while a hunt is `open`.
- Feature: New `[bhg_best_guessers]` shortcode with tabs (Overall, Monthly, Yearly, All-Time).
- UX: Ensure leaderboard affiliate indicators (green/red) always render via CSS.
- Admin: Minor coding-standards cleanups and nonces/cap checks verified.



## [Stage-2] Leaderboard History & Sorting Polish — 2025-09-03

- Added `[bhg_leaderboard_history]` shortcode to browse closed hunts and view their leaderboards with pagination.
- Made leaderboard headers clickable (Username, Guess, Position) with ASC/DESC toggles.
- Implemented safe 'position' sort in PHP while keeping SQL ORDER BY whitelisted to `guess_value` and `user_login` only.
- Preserved query args and pagination on sort changes.


## [Stage-3] Nextend Login UI — 2025-09-03

- Enhanced `[bhg_guess_form]` for guests: if Nextend Social Login is active, shows social login buttons (Google/Twitch/Kick). 
- Fallback: shows standard login link with redirect back to current page.


## [Stage-3] Nextend Login UI Integration — 2025-09-03

- Added Nextend Social Login buttons to the guest flow in `[bhg_guess_form]`, preserving smart redirect via `bhg_redirect`.
- Provides a safe fallback login link if Nextend is not active.


## [Stage-4] Ads Front-end Rendering — 2025-09-03

- Added `includes/class-bhg-ads.php` to render active ads on the front-end in the footer.
- Supports visibility filters: all, logged_in, guests, affiliates, non_affiliates.
- Safe output sanitization with `wp_kses_post` for messages and `esc_url` for links.
- Integrated into `bonus-hunt-guesser.php` with `BHG_Ads::init()`.


## [Stage-4] Ads Front-End Rendering — 2025-09-03

- Added `includes/class-bhg-ads.php` with a safe, minimal renderer for ads placed in the footer.
- Respects Admin ad config: `active`, `placement=footer`, `visibility` (all/logged_in/guests/affiliates/non_affiliates) and optional `target_pages` (comma-separated slugs).
- Output sanitization via `wp_kses_post()` for message and `esc_url()` for links.
- Bootstrapped from main plugin file and hooked into `wp_footer`.


## [Stage-4.1] Bugfix — 2025-09-03

- Fixed syntax error in `includes/class-bhg-ads.php` (`page_target_ok` method) by removing an extra closing parenthesis in `array_map` call.
