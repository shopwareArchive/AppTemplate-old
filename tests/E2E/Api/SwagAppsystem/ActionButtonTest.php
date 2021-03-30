<?php declare(strict_types=1);

namespace App\Tests\E2E\Api\SwagAppsystem;

use App\SwagAppsystem\ArgumentResolver\ClientResolver;
use App\SwagAppsystem\Client;
use App\Tests\E2E\Traits\ContractTestTrait;
use App\Tests\E2E\Traits\E2ETestTrait;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ActionButtonTest extends TestCase
{
    use E2ETestTrait;
    use ContractTestTrait;

    public function setUp(): void
    {
        $this->startServer();
    }

    public function tearDown(): void
    {
        $this->stopServer();
    }

    public function testActionButton(): void
    {
        $randomId = bin2hex(random_bytes(16));
        $httpClient = $this->getClient()->getHttpClient();

        $requestContent = json_decode(file_get_contents(__DIR__ . '/_fixtures/actionButtonResponse.json'), true);
        $requestContent['source']['shopId'] = $this->getShopId();
        $requestContent['data']['ids'] = [$randomId];

        $signature = \hash_hmac('sha256', json_encode($requestContent), $this->getShopSecret());

        //create expected request for argument resolver
        $request = new Request([], [], [], [], [], [], json_encode($requestContent));
        $request->setMethod('POST');
        $request->headers->add(['shopware-shop-signature' => $signature]);

        $clientArgument = new ArgumentMetadata('Client', Client::class, false, false, null, true);
        $clientResolver = new ClientResolver($this->getShopRepository());

        //validate expected request
        static::assertTrue($clientResolver->supports($request, $clientArgument));
        static::assertInstanceOf(Client::class, $clientResolver->resolve($request, $clientArgument)->current());

        //add matcher to request content
        $requestContent['meta']['timestamp'] = (new Matcher())->integer();
        $requestContent['meta']['reference'] = (new Matcher())->regex($randomId, '[A-Za-z0-9]{32}');

        //create expected request
        $request = new ConsumerRequest();
        $request
            ->setPath('/actionbutton/order')
            ->setMethod('POST')
            ->setHeaders(['shopware-shop-signature' => (new Matcher())->regex($signature, '[A-Za-z0-9]{64}')]) //unable to predict signature due to the timestamp
            ->setBody($requestContent);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('/actionbutton/order POST')
            ->with($request)
            ->willRespondWith($response);

        //get action button id
        $result = $httpClient->get('api/app-system/action-button/order/detail');
        foreach (json_decode($result->getBody()->getContents(), true)['actions'] as $action) {
            if ($action['url'] === 'http://example:5000/actionbutton/order') {
                $actionButtonId = $action['id'];
                break;
            }
        }

        //trigger action button
        $httpClient->post(
            sprintf('api/app-system/action-button/run/%s', $actionButtonId),
            [
                'body' => json_encode([
                    'ids' => [
                        $randomId,
                    ],
                ]),
            ]
        );

        //verify interaction
        static::assertTrue($builder->verify());
    }
}
