##Getting started

At first you need to configure the `.platform.app.yaml`  
In there you need to set an `APP_NAME` and also an `APP_SECRET`  
These must match the one in the `manifest.xml`

An example would be
```yaml
variables:
    env:
        APP_NAME: myExampleApp
        APP_SECRET: myAppSecret
```

These should also be changed in the `.env` to develop locally. 

##Development

This is a symfony based development template.  
To register your app you only need to configure your manifest.  
The registration URL is `https://www.my-app.com/registration`

The `SwagAppsystem\Client` and `SwagAppsystem\Event` will be injected when you need them.
For example: 

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\SwagAppsystem\Client;
use App\SwagAppsystem\Event;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class OrderController
{
    /**
     * @Route("/order/placed/event", methods={"POST"})
     */
    public function orderPlacedEvent(Client $client, Event $event): Response
    {
        //nth
    }
}
```

In there you can use the `$client` to communicate with the shop or use the `$event` to get the data.

##App lifecycle events

There are five app lifecycle events which can be triggered during the lifecycle of an app.  
The events are `app_installed`, `app_updated`, `app_deleted`, `app_activated` and `app_deactivated`.
To use this events you have to create the webhooks in your manifest.  
If you want to implement your own code you only need to implement the [AppLifecycleHandler](src/SwagAppsystem/AppLifecycleHandler.php) interface and write your own code.  

The `app_installed` event gets triggered each time the app gets installed.  
This will also trigger the `app_activated` event.  
At each of this both events the shop is already installed and registered at your app.  
The webhook could look like this:

```xml
<webhook name="appLifecycleInstalled" url="https://your-shop-url/applifecycle/installed" event="app_installed"/>
```

The `app_updated` event gets triggered each time a shop updated your app.  
The webhook could look like this:

```xml
<webhook name="appLifecycleUpdated" url="https://your-shop-url/applifecycle/updated" event="app_updated"/>
```

The `app_deleted` event gets triggered each time a shop deletes your app.  
At this point the shop is deleted using the [shopRepository](src/Repository/ShopRepository.php).  
You should delete all shop data you have saved and stop the communication with the shop.  
The webhook could look like this:

```xml
<webhook name="appLifecycleDeleted" url="https://your-shop-url/applifecycle/deleted" event="app_deleted"/>
```

The `app_activated` event gets triggered each time your app gets installed or activated.  
At this point you can start the communication with the shop.  
The webhook could look like this:
```xml
<webhook name="appLifecycleActivated" url="https://your-shop-url/applifecycle/activated" event="app_activated"/>
```

The `app_deactivated` event gets triggered each time your app gets deactivated.  
At this point you should stop the communication with the shop.  
The webhook could look like this:

```xml
<webhook name="appLifecycleDeactivated" url="https://your-shop-url/applifecycle/deactivated" event="app_deactivated"/>
```

##Testing

To test your app you can use [PHPUnit](https://phpunit.de/index.html)  
You can write your own tests in `tests`  
and execute them executing `vendor/bin/phpunit`

To check your codestyle you can use [EasyCodingStandard](https://github.com/symplify/easy-coding-standard)  
Just execute `vendor/bin/ecs check` to check your code or add `--fix` to also fix your code.

##Deployment on platform.sh

To deploy your app on [platform.sh](platform.sh) just follow the instructions:
* [Public GitHub repository](https://docs.platform.sh/integrations/source/github.html)
* [Private GitHub repository](https://docs.platform.sh/development/private-repository.html)
* [Using the Platform.sh CLI](https://github.com/platformsh/platformsh-cli)

After the deployment you can use the [Plaform.sh CLI]((https://github.com/platformsh/platformsh-cli)) to set up the database.
First ssh to your server: `platform ssh`  
Then run the migrations: `vendor/bin/doctrine-migrations migrations:migrate`  
That's is. Your server is running and you can start developing your own app. 