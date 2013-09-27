omeka-purl-plugin
=================

PURL plugin for omeka.

this plugin creates PURLs for item objects and adds identifiers (dc-identifier).

this plugin manages PURL status/type as well.

this plugin uses asynchronous long running jobs for PURL ops.

PURLs (type 302) are created:

- for existing public items when you install this plugin;
- for every newly created public item;
- when an existing private item becomes public for the first time.

PURLs (type 302 / 404) are modified:

- when a public item becomes private (PURL is set to 404);
- when a private(-previously-public) item becomes public again (PURL is set to 302).

PURLs are deleted (=tombstoned):

- when an item is deleted.

you need an oclc-zepheira PURL(Z) server to send requests to.

please, remember to set your purl_server, your maintainer_id, ..., values in plugin.php (I know a config form would be better...).

I decided to use Guzzle as REST client to send requests to the PURL(Z) server. It seems to me it's a good choice because it handles authentication and cookies.

purl ops log are stored in mysql.

for omeka > 2.0




notes:

this is the very first version of the plugin and better code and docs are coming soon.


FUTURE STEPS

- Callimachus 1.2 / 1.3

