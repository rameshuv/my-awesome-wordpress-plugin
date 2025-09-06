Bonus Hunt Guesser — Stage B Patch
Generated: 2025-09-05T03:18:38.706805
Requires at least: 6.3.5

WHAT THIS PATCH DELIVERS
- Adds "Results" button to the hunts list (Admin → Bonus Hunts).
- Adds "Final Balance" column to the hunts list.
- Enriches Hunt Edit page with "Participants" table:
  - Lists all guessers (30 per page), username is clickable to WP user edit.
  - Allows removing a guess (with nonce confirmation).
- Updates helpers to support participants retrieval and guess removal.

FILES INCLUDED (drop-in)
- admin/views/hunts-list.php                (DROP-IN replacement)
- admin/views/hunts-edit.php                (DROP-IN replacement/extension; safe to include below your existing form)
- includes/class-bhg-bonus-hunts-helpers.php (UPDATED to include new helper functions)

HOW TO APPLY
1) Copy the files into your plugin directory, replacing the existing ones where names match.
   If your plugin uses slightly different filenames, keep the file contents and paste into your equivalents.

2) Ensure your admin menu routes:
   - Hunts list page slug:      admin.php?page=bhg-hunts
   - Hunt edit page slug:       admin.php?page=bhg-hunts-edit&id={hunt_id}
   - Hunt results page slug:    admin.php?page=bhg-hunt-results&id={hunt_id}  (from Stage A)
   If your slugs differ, adjust the links at the top of each view accordingly.

3) Make sure Stage A is applied first (it registers the Results page and helpers).

4) Commit & Push:
   git add admin/includes
   git commit -m "Stage B: Hunts list Results button + Final Balance; Edit page participants with removal; helpers update"
   git push

QA QUICK CHECK
- Admin → Bonus Hunts: verify "Final Balance" column shows values or — when null.
- Click "Results" for a closed hunt: navigates to full ranking page (from Stage A).
- Edit a hunt: scroll to "Participants" → remove a test guess; verify it disappears.
- Pagination controls work at 30 per page.

Rollback
- Replace the three files with previous versions (or git revert the commit).
