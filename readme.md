Installation:

Install Composer (https://getcomposer.org/download/) globally.

Required PHP packages ("composer install" command (see below) will tell if any other packages are missing for your OS):

php8.2, php8.2-mbstring, php8.2-curl, php8.2-zip, php8.2-intl, php8.2-xml, php8.2-gd, php8.2-mysql

Enable rewrite module for Apache2 if needed (a2enmod rewrite).

Code adjustments:

- from app root directory: mv config/settings.php.dist config/settings.php
- vim config/settings.php (instructions included in file)

Vendor installation:

composer install

DB Creation:

- Create the DB and user in MySQL/MariaDB as specified in config/settings.php.
- from app root directory: php vendor/bin/doctrine orm:schema-tool:create

VHOST setup:

- Set up VHOST (example included in delivered package)
- tmp/ and logs/ need to be writeable by web server user (chown/chmod)
