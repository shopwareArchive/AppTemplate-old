<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace App\SWAppsystem;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Authenticator implements EventSubscriberInterface
{
    private $shopClient;

    public function __construct(Client $shopClient)
    {
        $this->shopClient = $shopClient;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    public function onRequest(RequestEvent $event)
    {


        $token = json_decode($authRes->getBody(), true)['access_token'];
    }

}