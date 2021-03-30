<?php declare(strict_types=1);

namespace App\Tests\E2E\Api\SwagAppsystem;

use App\SwagAppsystem\ArgumentResolver\ClientResolver;
use App\SwagAppsystem\ArgumentResolver\EventResolver;
use App\SwagAppsystem\Client;
use App\SwagAppsystem\Event;
use App\Tests\E2E\Traits\ContractTestTrait;
use App\Tests\E2E\Traits\E2ETestTrait;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class WebhookTest extends TestCase
{
    use E2ETestTrait;
    use ContractTestTrait;

    public function tearDown(): void
    {
        $this->stopServer();
    }

    public function testWebhook(): void
    {
        $randomString = bin2hex(random_bytes(16));

        $requestContent = json_decode(file_get_contents(__DIR__ . '/_fixtures/productWrittenEvent.json'), true);

        //create product to update
        $taxID = $this->getClient()->search('tax', [])['data'][0]['id'];
        $currencyID = $this->getClient()->search('currency', [])['data'][0]['id'];
        $productData = [
            'id' => $randomString,
            'name' => 'my product',
            'taxId' => $taxID,
            'price' => [
                [
                    'currencyId' => $currencyID,
                    'gross' => '100',
                    'linked' => true,
                    'net' => '90',
                ],
            ],
            'productNumber' => $randomString,
            'stock' => 100,
        ];

        //create product
        $this->getClient()->createEntity('product', $productData);

        //set current shopId and productId
        $requestContent['source']['shopId'] = $this->getShopId();
        $requestContent['data']['payload'][0]['primaryKey'] = $productData['id'];

        $signature = \hash_hmac('sha256', json_encode($requestContent), $this->getShopSecret());

        $request = new Request([], [], [], [], [], [], json_encode($requestContent));
        $request->setMethod('POST');
        $request->headers->add(['shopware-shop-signature' => $signature]);

        $clientResolver = new ClientResolver($this->getShopRepository());
        $eventResolver = new EventResolver($this->getShopRepository());

        $clientArgument = new ArgumentMetadata('Client', Client::class, false, false, null, true);
        $eventArgument = new ArgumentMetadata('Event', Event::class, false, false, null, true);

        //make sure created request is valid
        static::assertTrue($clientResolver->supports($request, $clientArgument));
        static::assertInstanceOf(Client::class, $clientResolver->resolve($request, $clientArgument)->current());

        static::assertTrue($eventResolver->supports($request, $eventArgument));
        static::assertInstanceOf(Event::class, $eventResolver->resolve($request, $eventArgument)->current());

        //create expected request
        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/product/written')
            ->setHeaders(['shopware-shop-signature' => $signature])
            ->setBody($requestContent);

        $response = new ProviderResponse();
        $response->setStatus(204);

        //start mock server
        $this->startServer();

        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /product/written webhook')
            ->with($request)
            ->willRespondWith($response);

        //trigger webhook which will trigger the expected request
        $this->getClient()->updateEntity('product', $productData['id'], ['name' => $randomString]);

        //wait until entity was updated
        $this->getClient()->getHttpClient()->post('api/_action/message-queue/consume', [
            'body' => json_encode([
                'receiver' => 'default',
            ]),
        ]);

        static::assertTrue($builder->verify());
    }
}
