## About this
This repository is part of the ManiaCDN project, a free CDN for and by the Maniaplanet community. It's the central website to upload and browse data on the network. You can use it on http://upload.maniacdn.net/. Please note that a Maniaplanet account is required to use it.

## Technologies used

 - PHP 7
 - Symfony
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

Create a copy of .env and call it .env.local. Adjust it to your needs.

Finally run the database migrations to set it up / update it. The database should be empty.

    $ bin/console doctrine:migrations:migrate 

To run a development server, you can install the [Symfony CLI](https://symfony.com/download). Then run

    $ symfony server:start
	
Keep the terminal / commandline open. You'll find the sample on http://localhost:8000/

For people familiar with Symfony, things should go pretty straightforward. I have tried to comply with Symfony Best Practices as much as possible, so you should find all files where you'd expect them.
You might also delete the pre-existing Migrations in src/Migrations, if doctrine throws strange errors while executing the migration.
