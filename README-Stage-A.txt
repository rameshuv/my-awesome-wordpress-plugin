Bonus Hunt Guesser — Stage A Patch
Generated: 2025-09-05T03:13:45.120802
Requires at least: 5.5.5

WHAT THIS PATCH DELIVERS
- Fixes parse error in admin/views/translations.php
- Adds Dashboard page "Latest Hunts" (admin/views/dashboard.php)
- Adds Hunt Results page listing all guesses with winners highlighted (admin/views/hunt-results.php)
- Adds affiliate status dot helper (includes/helpers-aff-dot.php) and minimal CSS (assets/css/bhg.css)
- Adds DB upgrade helper to ensure winners_limit column (includes/upgrade/add-winners-limit.php)
- Provides helper functions for winners/results (includes/class-bhg-bonus-hunts-helpers.php)

HOW TO APPLY (SAFE)
1) Copy files into your plugin:
   - admin/views/dashboard.php
   - admin/views/hunt-results.php
   - admin/views/translations.php   (replace your current file)
   - includes/helpers-aff-dot.php   (new)
   - includes/class-bhg-bonus-hunts-helpers.php (new)
   - includes/upgrade/add-winners-limit.php     (new)
   - assets/css/bhg.css (new; enqueue it where appropriate)

2) In bonus-hunt-guesser.php add (once) after constants:
   require_once __DIR__ . '/includes/class-bhg-bonus-hunts-helpers.php';
   require_once __DIR__ . '/includes/helpers-aff-dot.php';
   require_once __DIR__ . '/includes/upgrade/add-winners-limit.php';

   // Run DB upgrade on admin init if version bumped
   add_action('admin_init', 'bhg_upgrade_add_winners_limit_column');

3) Admin menu update (rename submenu and add pages):
   - In your admin menu registration (admin/class-bhg-admin-menu.php or equivalent), replace the submenu label "bonushunt" with "Dashboard" and point it to:
       function bhg_admin_render_dashboard(){ include __DIR__ . '/views/dashboard.php'; }
   - Add a submenu slug "bhg-hunt-results" that calls:
       function bhg_admin_render_hunt_results(){ include __DIR__ . '/views/hunt-results.php'; }

4) Shortcodes default sort fix:
   - In includes/class-bhg-shortcodes.php ensure:
       $atts = shortcode_atts(array('orderby' => 'position','order' => 'ASC'), $atts, 'bhg_user_guesses');
       $orderby = in_array($atts['orderby'], array('position','username','guess'), true) ? $atts['orderby'] : 'position';
       $order   = (strtoupper($atts['order']) === 'DESC') ? 'DESC' : 'ASC';

5) Conditional tags:
   - If you use is_search()/is_embed() in plugin load, move them to run after 'wp' action.

6) Enqueue CSS (optional but recommended):
   - In admin_enqueue_scripts or frontend enqueue:
       wp_enqueue_style('bhg-css', plugins_url('assets/css/bhg.css', __FILE__), array(), '1.0');

7) Test:
   - Create a hunt with winners_limit set (default 3), collect guesses, set final_balance, close hunt.
   - Dashboard -> Latest Hunts shows winners and diffs.
   - Results page ranks all guesses with top N highlighted.
   - Translations page saves and lists keys without errors.

Rollback:
   - All added files are isolated; remove them to revert Stage A additions.
