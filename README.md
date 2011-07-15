Maat
====

Maat is an extension for [Saft](https://github.com/bingobongo/saft). It lets you create, edit, remove, and uploading of content with a standard Web browser. Maat also enables basic collaboration. There is documentation on the [project page](http://doogvaard.net/speelplaats/2011/07/05/maat/).


Package Overview
----------------

	/app/
		maat/
			app.php
			authors/
				authorname.json
			blues/
				blues.php
				html.php
			elves/
				archive.php
				auth.php
				client.php
				colonel.php
				copilot.php
				env.php
				history.php
				httpdigest.php
			index/
				html.php
			lang/
				de.json
				en.json
			nav.php
			permalink/
				html.php
	/asset/
		maat/
			auth.js
			mootools-core.js
			mootools-more.js
			standard.css
			standard.js
	/.gitignore
	/LICENSE
	/README.md
	/VERSION


Installation
------------

Maat requires [Saft](https://github.com/bingobongo/saft/).

1. Configure the file `/app/maat/app.php`. Instructions on configuration are found inside it.
2. Turn on the debug mode of the corresponding Saft installation. Move `/app/maat` and `/asset/maat` to `/app` and `/asset` of the corresponding Saft installation.
3. Make sure that the file permissions of all items are set properly. Once it is successfully running, turn off again the debug mode of the corresponding Saft installation.


Read More
---------

See [doogvaard.net/speelplaats/2011/07/05/maat/](http://doogvaard.net/speelplaats/2011/07/05/maat/) for detailed information about **authors, collaboration, customization and other features.**


License
-------

Read the `LICENSE` for license and copyright details.
