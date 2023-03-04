# Installation

## Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following
command to download the latest stable version of this bundle:

```bash
$ composer require slince/cycle-bundle
````

This command requires you to have Composer installed globally

## Step 2: Enable the Bundle

Your bundle should be automatically enabled by Flex.
In case you don't use Flex, you'll need to manually enable the bundle by
adding the following line in the ``config/bundles.php`` file of your project

```php

// config/bundles.php

return [
    // ...
    Slince\CycleBundle\CycleBundle::class => ['all' => true],
    // ...
];
```