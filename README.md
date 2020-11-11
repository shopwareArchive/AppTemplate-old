## Platform-sh

The development template is optimized for the use with [platform.sh](https://platform.sh/).  

With this development template in addition with platform.sh you can easily develop your own app.  
You don't need to think about the hosting and communication with the shop.  
This will all be done by platform.sh and our controller and services. 

## Getting started

In order to use this template for development or for production you need to configure two things.  

* The `APP_NAME` (the unique name of your app)
* The `APP_SECRET` (a secret which is needed for the registration process)

You need to set both of them in your `manifest.xml` but also in the [.platform.app.yaml](.platform.app.yaml).

An example for the `manifest.xml` would be:

```xml
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/app-system/0.1.0/src/Core/Content/App/Manifest/Schema/manifest-1.0.xsd">
    <meta>
        <name>myExampleApp</name>
    </meta>
    <setup>
        <secret>myAppSecret</secret>
    </setup>
</manifest>
```

An example for the [.platform.app.yaml](.platform.app.yaml) would be:
```yaml
variables:
    env:
        APP_NAME: myExampleApp
        APP_SECRET: myAppSecret
```

These should also be changed in the `.env` to develop locally. 

## Development

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

## App lifecycle events

There are five app lifecycle events which can be triggered during the lifecycle of an app.  
The events are `app.installed`, `app.updated`, `app.deleted`, `app.activated` and `app.deactivated`.
To use this events you have to create the webhooks in your manifest.  
If you want to implement your own code you only need to implement the [AppLifecycleHandler](src/SwagAppsystem/AppLifecycleHandler.php) interface and write your own code.  

The `app.installed` event gets triggered each time the app gets installed.  
This will also trigger the `app.activated` event.  
At each of this both events the shop is already installed and registered at your app.  
The webhook could look like this:

```xml
<webhook name="appLifecycleInstalled" url="https://your-shop-url/applifecycle/installed" event="app.installed"/>
```

The `app.updated` event gets triggered each time a shop updated your app.  
The webhook could look like this:

```xml
<webhook name="appLifecycleUpdated" url="https://your-shop-url/applifecycle/updated" event="app.updated"/>
```

The `app.deleted` event gets triggered each time a shop deletes your app.  
At this point the shop is deleted using the [shopRepository](src/Repository/ShopRepository.php).  
You should delete all shop data you have saved and stop the communication with the shop.  
The webhook could look like this:

```xml
<webhook name="appLifecycleDeleted" url="https://your-shop-url/applifecycle/deleted" event="app.deleted"/>
```

The `app.activated` event gets triggered each time your app gets installed or activated.  
At this point you can start the communication with the shop.  
The webhook could look like this:
```xml
<webhook name="appLifecycleActivated" url="https://your-shop-url/applifecycle/activated" event="app.activated"/>
```

The `app.deactivated` event gets triggered each time your app gets deactivated.  
At this point you should stop the communication with the shop.  
The webhook could look like this:

```xml
<webhook name="appLifecycleDeactivated" url="https://your-shop-url/applifecycle/deactivated" event="app.deactivated"/>
```

## Testing

To test your app you can use [PHPUnit](https://phpunit.de/index.html)  
You can write your own tests in `tests`  
and execute them executing `vendor/bin/phpunit`

To check your codestyle you can use [EasyCodingStandard](https://github.com/symplify/easy-coding-standard)  
Just execute `vendor/bin/ecs check` to check your code or add `--fix` to also fix your code.

## Deployment on platform.sh

To deploy your app on [platform.sh](platform.sh) just follow the instructions:
* [Public GitHub repository](https://docs.platform.sh/integrations/source/github.html)
* [Private GitHub repository](https://docs.platform.sh/development/private-repository.html)
* [Using the Platform.sh CLI](https://github.com/platformsh/platformsh-cli)

After the deployment you can use the [Plaform.sh CLI](https://github.com/platformsh/platformsh-cli) to set up the database.
First ssh to your server: `platform ssh`  
Then run the migrations: `vendor/bin/doctrine-migrations migrations:migrate`  
That's is. Your server is running and you can start developing your own app. 

## The registration process 

The registration is the most important thing in your app.  
To handle this we have the [Registration](src/SwagAppsystem/Controller/Registration.php) controller.  
This controller will handle the whole registration.  

The registration will go through several steps.

* authenticate the registration request
* generate an unique secret for the shop
* save the secret with the id and the url of the shop
* send the secret to the shop with a confirmation url
* authenticate the confirmation request
* save the access keys for the shop

Now the shop is registered to the app and you can start communicating with it. 

## Communicating with the shop

To communicate with the shop you can use the [Client](src/SwagAppsystem/Client.php).  
The client includes all necessary functionality for communication purposes.  

It will authenticate itself to the shop whenever needed.  
For example if you want to search a specific product it will first authenticate itself to get the bearer token from the shop.  
Then it will set the necessary headers which are needed and then perform your search.  

If there is some functionality which isn't implemented into the client you can simply get your own client with `$client->getHttpClient`.  
This client has already the needed header and token to communicate with the shop.  
Now you can perform your own requests.  

## Handling events

In your manifest you can define your own webhooks.  
To handle these in your app we included the [Event](src/SwagAppsystem/Event.php).  
You can use it whenever an event gets triggered.  

The event itself has all the necessary information you might need.  
It includes the `shopUrl`, `shopId`, `appVersion` and the `eventData`.  

## The argument resolver

There are two argument resolver. One for the [Client](src/SwagAppsystem/Client.php) and one for the [Event](src/SwagAppsystem/Event.php).  
The purpose of those is to inject the [Client](src/SwagAppsystem/Client.php) and the [Event](src/SwagAppsystem/Event.php) whenever you need them.  

For example you define a route for incoming webhooks and want to fetch some extra data.  
Then you can use them as a parameter of the method which will be called when a request is send to the route.  

But how do you know that the request is from the shop and not from someone who is sending post requests to your app?
The argument resolver take care of it. Whenever you use one of them as a parameter the request will be authenticated.  
If the request isn't authenticated the [Client](src/SwagAppsystem/Client.php) or the [Event](src/SwagAppsystem/Event.php) will be null. 

## The shop repository

The [ShopRepository](src/Repository/ShopRepository.php) can be used to get the secret of the shop and the [Credentials](src/SwagAppsystem/Credentials.php).  

For example if you want to build your own authentication you can use the [ShopRepository](src/Repository/ShopRepository.php) to get the secret to the corresponding shop.  
But if you want to build your own [Client](src/SwagAppsystem/Client.php) you can simply get the [Credentials](src/SwagAppsystem/Credentials.php) for a specific `shopId`.  

## Infrastructure

Let's talk about the infrastructure.  
The infrastructure is coupled to your plan which you are paying for.  
Each resource whether it is CPU and RAM or disc space is only for one environment / cluster.  
It is not shared between multiple environments / clusters.

#### CPU and RAM

The resources for cpu and ram are shared between all your container in the cluster.  
If one container in your application needs much more ram than another application then you can set the resources with the `size` key.  
You can configure this for your application in your [.platform.app.yaml](.platform.app.yaml).  
And configure this for your services in your [services.yaml](.platform/services.yaml).
  
This key is optional and by default set to `AUTO`.  
However if you want to change it, you can set it to `S`, `M`, `L`, `XL`, `2XL` or `4XL`.  
This defines how much resources one container gets.  
If the total resources requested by all apps and services is larger than that what the plan size allows  
then a production deployment will fail with an error.

You need to keep in mind that the `size` key only has impact on your production environment.  
The key will be ignored in the development environment and will be set to `S`.  
If you need to increase this you can do it on you plan settings page for a fee.    

#### Disc space

Another thing you can configure is the disk space of each application and service.  
You can also configure this in [.platform.app.yaml](.platform.app.yaml) and [services.yaml](.platform/services.yaml).

The resources for the disc space are also shared between all container in the cluster.  
The key for this is the `disk` key.  
It is optional so if you don't set it platform-sh will handle it for you.  
However if you need much storage for your database then you can change this key in your [services.yaml](.platform/services.yaml).

The value of this key is always in MB.  
For our example we used 2GB or 2048MB for our application and another 2GB or 2048MB for our database.  

The default storage you get with each plan is 5GB or 5120MB.  
In our case we only used 4GB or 4096MB so you have 1GB or 1024 left  
which you can give to your application or to your database.   

Whether you use it or not won't affect your costs.

## Code quality

To improve your code style we added [EasyCodingStandard](https://github.com/symplify/easy-coding-standard) and for testing purposes [PHPUnit](https://phpunit.de/index.html).

To check your code style just execute `vendor/bin/ecs check` or add `--fix` to also fix your code.

To make sure that your code is working correctly you can write your own tests in `tests`.  
In order to execute those just execute `vendor/bin/phpunit`.  
