basic-sonata
============

A Symfony project created on July 6, 2015, 7:50 pm.
# sonata




## Installation
To install this app do the following steps:

```bash
git clone https://github.com/Richard-NL/sonata.git
```

Edit app/config/parameters to match your db settings

```bash
composer install
chmod -R 777 app/logs app/cache
app/console doctrine:database:create
app/console doctrine:schema:create
app/console sonata:page:create-site
```
cd into /project-name/web and type bower install ../vendor/sonata-project/admin-bundle/bower.json

you can also run the quickstart.bash from the console