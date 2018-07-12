## About this
This repository is part of the ManiaCDN project, a free CDN for and by the Maniaplanet community. It's the central website to upload and browse data on the network. You can use it on http://upload.manicdn.net/

## Technology used

 - PHP 7.2
 - Symfony 4.1
 - Bootstrap 3 + Glyphicons
 - Dropzone.js uploader
 - Symfony bundles:
	 - Oneup uploader bundle
	 - Knpu OAuth2 client

## Contributing/Installation
You can open up an issue here on Github or send us an email to info [at] maniacdn [dot] net.
If you would like to run the site yourselves, continue here:
Clone the repo with git (or get the zip on the top-right)

	$ git clone git@github.com:ManiaCDN/uploader.git

Change into the directory that was just created by cloning/unpacking

    $ cd uploader

And tell composer to get the libraries (*vendor* folder) for you

    $ composer install

That's it already. Now you're ready to start the development server of Symfony:

	bin/console server:run
	
Keep the terminal / commandline open. You'll find the sample on http://localhost:8000/

## Going on
For people familiar with Symfony, things should go pretty straightforward. Don't forget to setup the .env according to your environment. I have tried to comply with Symfony Best Practices as much as possible, so you should find all files where you'd expect them.
