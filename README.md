# Fairfax Category Sync
Syncs the local blog categories with the categories from an example REST API.

## Installation
1. Head to [releases](https://github.com/aprea/fairfax/releases) and download a zip archive of this plugin.
2. In your WordPress installation, head to Plugins -> Add New.
3. Upload and activate the plugin.

## Requirements
- PHP 7.

## Usage Instructions
**On-demand Sync**
1. In your WordPress installation, head to Settings -> General.
2. Scroll to the bottom of the settings page, you'll find a "Update Categories" section.
3. Click the "Update categories now" button to perform an on-demand blog category sync.

**Cron Sync**
A cron job is registered on plugin activation that will sync the blog categories every 30 minutes automatically.

## Bonus
Head to http://fairfax.chrisaprea.com/wp-admin to demo the plugin. The login credentials were provided in an email sent to the email address specified at the bottom of the brief.

## Time
This plugin and related tasks took 3.5 hours to complete.

 ## TODO
 - Was unable to disable category creation in the time allocated. My initial thought was to simply disable the `manage_categories` capability but that would not only stop category creation but would also kill overall category management.
 - Better error handling.
 - Better internationalization.
 - Create a `sync_categories` capability rather than piggybacking off `manage_options`.

## Test Feedback
This was a very good test! I like that it was WordPress specific and not just a generic PHP test.
