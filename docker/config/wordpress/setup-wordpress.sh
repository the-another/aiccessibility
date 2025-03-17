#!/bin/sh


echo "Running WordPress setup"

function runwpcli()
{
  echo "Running WP-CLI command: wp $@"
  wp --allow-root --path='/var/www/html/' $@
}

# Check if WordPress is downloaded
if [ ! -f /var/www/html/wp-includes/version.php ]; then
  echo "WordPress is not downloaded, trying to download"
  runwpcli core download

  if [ ! -f /var/www/html/wp-includes/version.php ]; then
    echo "WordPress download failed, exiting"
    exit 1
  fi
fi

# Create wp-config file if it doesn't exist
if [ ! -f /var/www/html/wp-config.php ]; then
  echo "wp-config.php missing, trying to create new one"

  runwpcli config create --dbname="wordpress" --dbuser="user" --dbpass="password" --dbhost="mariadb" --extra-php <<PHP

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );

define( 'WP_MEMORY_LIMIT', '256M' );

PHP

  if [ ! -f /var/www/html/wp-config.php ]; then
    echo "wp-config.php creation failed, exiting"
    exit 1
  fi
fi

# Install WordPress if it's not already installed by using WP-CLI
if ! runwpcli core is-installed; then
  echo "WordPress is not installed, trying to install"
  runwpcli core install --url="http://localhost:8080" --title="WordPress" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com" --skip-email

  if ! runwpcli core is-installed; then
    echo "WordPress installation failed, exiting"
    exit 1
  fi

  echo "2025 theme is missing, trying to install and activate"
  # make sure 2025 theme is installed and activated
 runwpcli theme install twentytwenty --activate

 if (! runwpcli theme is-active twentytwenty); then
    echo "Twenty Twenty theme activation failed, exiting"
    exit 1
  fi
fi

if runwpcli core is-installed; then
  # Make sure WordPress URL to matches the container's URL
  runwpcli option update siteurl "http://localhost:8080"
  runwpcli option update home "http://localhost:8080"

  runwpcli rewrite structure '/%year%/%monthnum%/%postname%/'
fi

echo "WordPress setup finished"

echo "Adjust WordPress permissions"
chown -R www-data:www-data /var/www/html
