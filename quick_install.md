# Quick Install Guide

This guide has been written for Ubuntu 20.04 LTS and tested on Ubuntu 20.04 on Windows Subsystem for Linux 2 (WSL2).

## Step 1: Installation

Make sure everything is up to date:

```bash
sudo apt update
sudo apt upgrade
```

Install PHP (with extensions):

```bash
sudo apt install php php-cli php-pgsql php-curl php-mbstring php-zip php-bcmath php-xml
```

Install Postgres:

```bash
sudo apt install postgresql postgresql-contrib
```

Install Composer:

```bash
sudo apt install composer
```


## Step 2: Setup Database

Start the postgres service (if it hasn't started yet):

```bash
sudo systemctl start postgresql
```
or if you're in WSL:

```bash
sudo service postgresql start
```

Create a `webcie` user:

```bash
sudo -u postgres createuser --interactive --pwprompt webcie
```

Enter password and answer no to all other questions.

Create a database:

```bash
sudo -u postgres createdb -O webcie --encoding=UTF8 --template=template0 webcie
```

If you get `WARNING:  could not flush dirty data: Function not implemented` (may happen on WSL1), interrupt (`CTRL+C`) and do the following:  

Open `/etc/postgresql/<postgres version number>/main/postgresql.conf` and change the following settings:

```
bgwriter_flush_after = 0
backend_flush_after = 0
fsync = off
wal_writer_flush_after = 0
checkpoint_flush_after = 0
```


## Step 3: Setup Repository

Clone the repository and `cd` into its directory. If you're using WSL make sure to clone it to a location you can easily access from Windows.

Copy the config files:

```bash
cp config/config.inc.default config/config.inc
cp config/DBIds.php.default config/DBIds.php
```

Adjust `config/DBIds.php` to match your database settings.

Install PHP dependencies:

```bash
composer install
```

Load barebone database:

```bash
sudo -u postgres psql webcie < src/data/webcie-minimal.sql
```

Set password for test user (ID = 1):

```bash
php bin/set-password.php
```


## Step 4: Run locally


To run the website, execute the following in the root folder of your repository:

```bash
php -S localhost:8000/
```

Now, you should be able to load `localhost:8000/` in a browser and log in with `test@svcover.nl` and the password you just set.

If php crashes on a segmentation fault, try running the following command instead: 

```bash
php -d opcache.enable=0 -d opcache.enable_cli=0 -S localhost:8000/
```

Please note that the barebone database is quite empty. If you need more content, you should add it yourself. The `test@svcover.nl` user is a member of the AC/DCee in this setup, so you should be able to do anything you need with this user. Feel free to create more users if you want.


## Step 5: Fix additional functionality

Some things will not work with this setup.

### Fixing config

Photo albums will not show photos. To fix this, change the `url_to_scaled_photo` setting in `config/config.inc` to `'https://www.svcover.nl/fotoboek.php?view=scaled'`,

Some pages will complain that you didn't configure a nonce salt. To fix this, change the `nonce_salt` setting in `config/config.inc` to any string of your liking (or generate one according to the instructions).

### ImageMagick

Profile pictures won't render correctly. For this you need ImageMagick. This can be installed for your PHP installation by running

```bash
sudo apt install php-imagick
```

This should fix the issue (in theory at least).
