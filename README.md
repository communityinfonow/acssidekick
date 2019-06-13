ACS Sidekick - README

The ACS Sidekick is a single-page Laravel application and a set of related tools
for simplifying access to certain data released by the US Census Bureau.

**Installation**

*Install dependencies for Laravel:*
* PHP >= 7.1.3
* BCMath PHP Extension
* Ctype PHP Extension
* JSON PHP Extension
* Mbstring PHP Extension
* OpenSSL PHP Extension
* PDO PHP Extension
* Tokenizer PHP Extension
* XML PHP Extension

(See https://laravel.com/docs/5.8/installation)

*Install Composer*
```php
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

(See https://getcomposer.org/download/)

*Clone this repo*
```
git clone https://github.com/communityinfonow/acssidekick.git
```

*Set permissions for Laravel*
```
cd acssidekick
chown -R www-data.www-data storage/ bootstrap/cache
```

*Install dependencies with composer*
```
composer install
```

*Create (MySQL or compatible) database and user*
```sql
CREATE DATABASE acs_sidekick;
GRANT ALL PRIVILEGES on acs_sidekick.* to 'sidekick'*'localhost' IDENTIFIED BY 'somesecurepassword';
FLSUH PRIVILEGES;
```

*Create and configure .env file*
```
cp .env.example .env
vi .env
```
(Add YOUR configuration)

*Generate your unique application key*
```
php artisan key:generate
```

*Run migrations*
```
php artisan migrate
```

*Configure webserver*

Example Apache2 container:

```
    <VirtualHost *:80>
        ServerAdmin your@admin.email
        ServerName your.server.name
        DocumentRoot /path/to/acssidekick/public

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        <Directory /path/to/acssidekick/public>
            AllowOverride All
        </Directory>
```

At this point you should be able to visit the site at:
http://your.server.name/ and be greeted with a login screen.  Go ahead and register yourself.

You will see the following message:
> Your registration must be validated by an administrator.

*Make yourself an administrator*
```sql
USE acs_sidekick; --or whatever you named the DB
UPDATE users SET role='admin' WHERE email = 'your@email.com'; --or whatever address you registered with
```

Now if you revisit the app url, you should see the Query Builder.  You may manage other self-registered users via the "Admin" link in the left-hand navigation panel.  If you demote yourself from Admin and there are no other admin users established, run the sql query above to restore your admin rights.
