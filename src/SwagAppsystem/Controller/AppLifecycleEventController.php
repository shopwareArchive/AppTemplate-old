<?php declare(strict_types=1);

namespace App\SwagAppsystem\Controller;

use App\Repository\ShopRepository;
use App\SwagAppsystem\AppLifecycleHandler;
use App\SwagAppsystem\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppLifecycleEventController extends AbstractController
{
    private $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @Route("/applifecycle/installed", name="applifecycle.installed", methods={"POST"})
     * The event `app.installed` gets triggered each time your app gets installed.
     * At this point the shop is already registered.
     */
    public function appInstalled(Event $event): Response
    {
        /** @var AppLifecycleHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->appInstalled($event);
        }

        return new Response();
    }

    /**
     * @Route("/applifecycle/updated", name="applifecycle.updated", methods={"POST"})
     * The event `app.updated` gets triggered each time a shop updates your app.
     */
    public function appUpdated(Event $event): Response
    {
        /** @var AppLifecycleHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->appUpdated($event);
        }

        return new Response();
    }

    /**
     * @Route("applifecycle/activated", name="applifecycle.activated", methods={"POST"})
     * The event `app.activated` gets triggered each time your app gets activated.
     * This also happens after your app is installed.
     */
    public function appActivated(Event $event): Response
    {
        /** @var AppLifecycleHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->appActivated($event);
        }

        return new Response();
    }

    /**
     * @Route("/applifecycle/deactivated", name="applifecycle.deactivated", methods={"POST"})
     * The event `app.deactivated` gets triggered each time your app gets deactivated.
     * This don't happen when your app gets uninstalled.
     */
    public function appDeactivated(Event $event): Response
    {
        /** @var AppLifecycleHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->appDeactivated($event);
        }

        return new Response();
    }

    /**
     * @Route("/applifecycle/deleted", name="applifecycle.deleted", methods={"POST"})
     * The event `app.deleted` gets triggered each time your app gets uninstalled.
     */
    public function appDeleted(ShopRepository $shopRepository, Event $event): Response
    {
        /** @var AppLifecycleHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->appDeleted($event);
        }

        $shopRepository->removeShop($event->getShopId());

        return new Response();
    }
}
