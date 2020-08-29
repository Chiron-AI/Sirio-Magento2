# Sirio Module M2

## Installation

The extension must be installed via `composer`. To proceed, run these commands in your terminal:

```
composer require chiron/sirio
php bin/magento module:enable Chiron_Sirio
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
```

## Update

To update the extension to the latest available version (depending on your `composer.json`), run these commands in your terminal:

```
composer update chiron/sirio --with-dependencies
php bin/magento setup:di:compile
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
```

## Maintenance mode

You may want to enable the maintenance mode when installing or updating the module, __especially when working on a production website__. To do so, run the two commands below before and after running the other setup commands:

```
php bin/magento maintenance:enable
# Other setup commands
php bin/magento maintenance:disable
```
