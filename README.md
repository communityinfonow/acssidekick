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

**Adding Datasets**
Before you can use the system, you must add one or more datasets.  Supported datasets and geographies are configured in the `config/datasets.php` file:

```php
return [
    // Dataset configurations
    '2016_acs5' => [ // The 2016 Five Year American Community Survey
        'label' => '2016 ACS 5-year',
        'titles' => [
            'detail_tables' => [
                'base_url' => 'https://api.census.gov/data/2016/acs/acs5',
                'variable_file' => 'https://api.census.gov/data/2016/acs/acs5/variables.json',
                'value_type' => 'BIGINT(11)'
            ]
        ],
        'geographies' => [
            'US' => 'us',
            'REGION' => 'region',
            'DIVISION' => 'division',
            'STATE' => 'state',
            //'COUNTY' => 'county',
            //'STATAREA' => 'combined+statistical+area',
            //'ZCTA' => 'zip+code+tabulation+area'
        ],
        'geo_parents' => [
            'COUNTY' => array('STATE')
        ]
    ]
];
```
The primary key for the dataset is known as the "Dataset Name".  Each dataset you import will use its own MySQL database.  Before importing a dataset, you must create the database using the Dataset Name, and grant the application database user access to it:

```sql
CREATE DATABASE 2016_acs5;
GRANT ALL PRIVILEGES on 2016_acs5.* to 'sidekick'@'localhost';
FLUSH PRIVILEGES;
```

Now, make sure you added your census API key to the .env file:

```
CENSUS_API_KEY=a1b2c3d4e5f6a7b8d9e0fabcdefg123456780abc
```
(Use *your* key)

Now we can use the sidekick:getdata console command to pull data from the Census.gov api:
```
php artisan sidekick:getdata
```

Should return usage instructions:

```
usage: sidekick:getdata[dataset] [action] [table] [state]
 where [dataset] = key from config/datasets or 'list'
 where [action] = 'load' to load table data or 'list' to list table codes
 where [table] = a comma seperated list of valid table codes for the specified
   dataset, 'all' for all table codes, or 'resume' to continue an aborted 'all' load
   (note: 'resume' reloads the last attempted table)
 where [state] = a valid state code (##) or 'list'
```

Run `php artisan sidekick:getdata 2016_acs5 load B01001 48` to test loading the B01001 table for Texas.  If all goes well, you should see something like:
```
Importing B01001 ...
  Creating table US_B01001 ...
    Retrieving data ....+.+.+.+.+
  Creating table REGION_B01001 ...
    Retrieving data ....+.+.+.+.+
  Creating table DIVISION_B01001 ...
    Retrieving data ....+.+.+.+.+
  Creating table STATE_B01001 ...
    Retrieving data ....+.+.+.+.+
```
Once you are satisfied that the data import is working propoerly, you can run:
```
php artisan sidekick:getdata 2016_acs5 load all 48
```
This will load all the supported tables and geographies for state 48 (Texas).
*(Note, the more detailed geographies have been commented out in config/datasets.php.  These can be very time consuming to import.  Uncomment the entries to load them.)*

If the "load all" process is interrupted, you may run:
```
php artisan sidekick:getdata 2016_acs5 load resume 48
```

This will resume the load, restarting with the most recently created table.

**Building Metadata**

In order to expose the new dataset to the ACS Sidekick application, you must create metadata for the dataset you imported.  To do this, run `php artisan sidekick:getmeta`.  The `getmeta` process will create metadata for ALL tables and geographies in your dataset, even if you have only pulled a limited number of tables.  *Be patient, it takes a while.*
