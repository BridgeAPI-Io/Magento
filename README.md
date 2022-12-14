# Bridge module for Magento 2

The **Bridge module for Magento 2** is a PHP module which allows you to accept payments in your Magento 2 online store. It offers innovative features to reduce shopping cart abandonment rates, optimize success rates and enhance the purchasing process on merchants sites in order to significantly increase business volumes without additional investments in the Magento 2 e-commerce CMS solution.

## Getting started

### Composer installation

- Install module and dependencies using Composer: `composer require bridgepay/bridge-module-magento2 --no-dev`
- Run: `php bin/magento module:enable Bridgepay_Bridge`
- Run: `php bin/magento setup:upgrade`
- Run: `php bin/magento setup:static-content:deploy`
- Flush caches with: `php bin/magento cache:flush`

## Update with Composer

To update the extension to the latest available version (depending on your `composer.json`), run these commands in your terminal:

```
composer update bridgepay/bridge-module-magento2 --with-dependencies --no-dev
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

### Manual installation

- Install SDK using Composer: `composer require 202ecommerce/bridge-sdk:1.0.0`
- Copy/Paste module files to your Magento root directory
- Run: `php bin/magento module:enable Bridgepay_Bridge`
- Run: `php bin/magento setup:upgrade`
- Run: `php bin/magento setup:static-content:deploy`
- Flush caches with: `php bin/magento cache:flush`

## Maintenance mode

You may want to enable the maintenance mode when installing or updating the module, __especially when working on a production website__. To do so, run the two commands below before and after running the other setup commands:

```
php bin/magento maintenance:enable
# Other setup commands
php bin/magento maintenance:disable
```
********
## Compatibility

| Branch  | Magento versions  |
| ------- | ----------------- |
| `0.x`   | **>=** `2.2.x`    |

## Resources

- [Issues][project-issues] — To report issues, submit pull requests and get involved (see [Academic Free License][project-license])

## Features

## License

The **Bridge module for Magento 2** is available under the **Academic Free License (AFL 3.0)**. Check out the [license file][project-license] for more information.

[project-issues]: https://github.com/202ecommerce/bridge-module-magento2/issues
[project-license]: LICENSE.md
