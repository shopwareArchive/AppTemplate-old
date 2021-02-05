## Platform-sh

The development template is optimized for the use with [platform.sh](https://platform.sh/).  

With this development template in addition with platform.sh you can easily develop your own app.  
You don't need to think about the hosting and communication with the shop.  
This will all be done by platform.sh and our controller and services. 

## Getting started

In order to use this template for development or for production you need to configure two things.  

* The `APP_NAME` (the unique name of your app, the root app folder has to be named equally)
* The `APP_SECRET` (a secret which is needed for the registration process)

You need to set both of them in your `manifest.xml` but also in the [.platform.app.yaml](.platform.app.yaml).

An example for the `manifest.xml` would be:

```xml
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/app-system/0.1.0/src/Core/Content/App/Manifest/Schema/manifest-1.0.xsd">
    <meta>
        <name>myAppName</name>
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
The registration URL is `https://www.my-app.com/registration`.

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


## Local development with docker
#### Getting started

To develop your own apps locally, you need to do some changes to your existing Shopware development setup.  
I assume that you have already set up your local Shopware development setup.  
If not here you get more information about the setup [docs.shopware.com](https://docs.shopware.com/en/shopware-platform-dev-en/getting-started).  
At first, you should clone the app template from [GitHub](https://github.com/shopwareLabs/AppTemplate) and create a `manifest.xml` for your app.  
For further information about the `manifest.xml` have a look at our [documentation](https://docs.shopware.com/en/shopware-platform-dev-en/app-system-guide/setup#manifest-file).
Or for a fully working example based on this template watch out for our [appExample](https://github.com/shopwareLabs/AppExample).    

Your folder structure should look as follows:
```
...
│
├──development
│  ├──custom
│  │  └───apps
│  │      └───yourAppName
│  │          └───manifest.xml
│  │
│  ├──platform
│  └──...
│
└──shopwareAppTemplate
...
```

### Combining both in one docker setup

Once your Shopware development setup is ready to go you need to add your app to it.  
This is done by adding the services to your `development/docker-compose.yml`. 

At first, you need to add two networks. One for your app system and another one for combining the app system with Shopware.  
This is done by simply adding the networks `appSystem` and `development` to your existing ones:
```Yaml
networks:
    shopware:
    appSystem:
    development:
```
The `appSystem`-network is only for your app server and the app database.  
The `development`-network is used to combine your app server with the Shopware server.  

Now you need to define the `services` in your `development/docker-compose.yml`. Insert the following to firstly add your app server.   
```Yaml
services:
[...]
  example_app_server:
    image: shopware/development:local
    volumes:
      - "../shopwareAppTemplate:/app"
      - "~/.composer:/.composer"
    environment:
      CONTAINER_UID: 1000
      APPLICATION_UID: 1000
      APPLICATION_GID: 1000
    ports:
      - "127.0.0.1:7777:8000"
    networks:
      appSystem:
      development:
        aliases:
          - example
```
This adds a new container to your docker setup running your app server's code. The new container is available at the networks `appSystem` and `development`.  
In the `development`-network your app server has the alias `example`. This will be the url which your Shopware server needs to communicate with.  
This is also the url which you should use in your `manifest.xml` except for iframes.
The `volumes` represents the relative path to your app.  
And `ports` exposes port `8000` to `127.0.0.1:7777` to us so that we can go to `127.0.0.1:7777` or `localhost:7777` to directly connect to your app server.  
This will come in handy when we register your own modules to use iframes.  

The next step is to also add your mysql server to your docker setup.  
This is as easy as it was for your app server.  
Simply add this to your `development/docker-compose.yml`.
```Yaml
services:
[...]
  example_mysql:
    build: dev-ops/docker/containers/mysql
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_USER: app
      MYSQL_PASSWORD: app
    ports:
      - "5506:3306"
    volumes:
      - ./dev-ops/docker/_volumes/mysql-example:/mysql-data
    networks:
      appSystem:
        aliases:
          - appmysql
```
As you already know you connect your mysql server to the same network as your app server and give it the alias `appmysql`.  
Furthermore, you can now connect to your database on port `5506` from outside of the docker container.  
Last but not least we define the credentials for the mysql server and you are done with this.  

Now you need to add him to your `development`-network and give him an alias as follows:  
```Yaml
services:
[...]
  app_server:
    image: shopware/development:latest
    networks:
      shopware:
        aliases:
          - docker.vm
      development:
        aliases:
          - shopware
    extra_hosts:
      - "docker.vm:127.0.0.1"
    volumes:
      - ~/.composer:/.composer
    tmpfs:
      - /tmp:mode=1777
```
Now your app server can communicate with the Shopware server and your app server can also communicate with your app database.

### Access your app server via ssh

To easily access your app server via ssh, you need to create this script `development/dev-ops/docker/actions/ssh-app-server.sh`.
```shell script
#!/usr/bin/env bash

TTY: docker exec -i --env COLUMNS=`tput cols` --env LINES=`tput lines` -u __USERKEY__ -t __EXAMPLE_APP_SERVER_ID__ bash
```
This script can be executed from your `development` folder with `./psh.phar docker:ssh-app-server`.  
Keep in mind that this is only possible when the app server has been started with `./psh.phar docker:start` from your development folder.  

To make sure this script actually knows the ID of your app server which is running in the docker container, you need to define the `EXAMPLE_APP_SERVER_ID` in the `development/.psh.yaml.override`.  
Your `development/.psh.yaml.override` should look like this:
```Yaml
...
dynamic:
  ...
  EXAMPLE_APP_SERVER_ID: docker-compose ps -q example_app_server
  ...
...
```

### Initialising the server 

To initialise the app server and the app database you should access your app server via ssh and run `composer install --no-interaction`.  
Next you need to change your `shopwareAppTemplate/.env` and set the DATABASE_URL to `mysql://app:app@appmysql:3306/main`.  
This should look familiar to you because you just configured it in the `development/docker-compose.yml`.  

The next steps should be done while connected over ssh to your app server.  
Now you can set up the database by simply typing `bin/console doctrine:database:create`.  
This will create your database with name `main`.  
Then execute the migrations with `bin/console doctrine:migrations:migrate --no-interaction`. Now your database is ready.  

### Registration of local apps

This last step assumes that you already have a valid `manifest.xml` in the correct folder.  
In order to check this, make sure your `manifest` is in `development/custom/apps/yourAppName/manifest.xml`.  
Then access your local Shopware instance via ssh with `./psh.phar docker:ssh` and execute the check with `bin/console app:validate`.  
This will tell you if you provided a valid `manifest.xml`.

For the sake of simplicity you need to change the `APP_URL` of your Shopware instance to match the network-alias you gave him.  
This should be done in your `development/.psh.yaml.override` which should look like this:
```Yaml
...
const:
  ...
  APP_URL: "http://shopware"
  ...
...
```  

To make sure your `APP_URL` changed you need to reconnect to your Shopware instance via ssh with `./psh.phar docker:ssh`.  
Now your `APP_URL` changed and you can register your app via `bin/console app:refresh`.  This can also be done by `bin/console app:install yourAppName`.  
In order to access the Shopware admin you should run `./psh.phar administration:watch`. Now you can access the admin on `localhost:8080`. 

**Note:** Like with plugins, apps get installed as inactive. You can activate them by passing the `--activate` flag to the `app:install` command or by executing `app:activate`. 

### Working with iframes

Due to the fact that the aliases for your app server only work inside the docker container, you need to change it in the `manifest.xml`.  
In contrast to every other action, like webhooks or action buttons, iframes need to be accessible from outside the docker container.  
For this purpose iframes are the only thing in your `manifest.xml` where you need to set the source to `http://localhost:7777` as defined in the `development/docker-compose.yml`. 
