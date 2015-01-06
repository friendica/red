[b]Creating Documentation[/b]

To contribute documentation, simply put some words in a cunning order, and make their existence known to a developer.  You can do this literally anywhere as long as a developer can see it.  Once made aware, somebody will check it in for you.  You should try to avoid proprietary formats, or locations that require authentication with methods other than Zot in order to make it easy for a developer to access, but even this is not a strict requirement.

If you wish to contribute directly, that's fine too.  To contribute directly, documentation should be in one of the following formats:

[li]Markdown[/li]
[li]BBCode[/li]
[li]HTML[/li]
[li]Plain Text[/li]

Other formats are also allowed, but support for the format must be added to mod/help.php first.

If editing a plain text file, please keep column width to 80.  This is because plain text is used in instances where we may not have a working installation - the installation documentation, for example - and it should be easy to read these from a CLI text editor.

The advantage of Markdown is that it is human readable.

The advantage of BBCode is that it is identity aware.

Therefore, if using BBCode, try to make the most of it:
[li]Use ZRL links where appropriate to ensure a link to another site retains authentication and keeps identity based documentation working[/li]
[li]Use baseurl or observer.baseurl tags where appropriate instead of example.com for authenticated viewers.[/li]
[li]Support non-authenticated users with observer=0 tags.  We presently do not do this due to historical oversights.  This needs adding everywhere[/li]

[b]Translations[/b]

To translate documentation, or provided documentation in languages other than English:
[li]Create a directory in doc/ with your two letter country code if it doesn't already exist (eg, doc/de/ for German or doc/fr/ for French)[/li]
[li]Create a document with the same filename as the English version, but with content in your own language.  This allows us to fallback to the English if the translation for a particular page is not provided[/li]

To create documentation that has no equivalent file in English, you can create a new file with a name of your choosing - but you'll also need to provide a localised version of the index page (main.bb in English) to make it accessible from the menu.

#include doc/macros/main_footer.bb;
