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
