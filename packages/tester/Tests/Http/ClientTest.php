<?php

namespace Draw\Component\Tester\Tests\Http;

use Draw\Component\Tester\Http\Client;
use Draw\Component\Tester\Http\ClientInterface;
use Draw\Component\Tester\Http\ClientObserver;
use Draw\Component\Tester\Http\RequestExecutionerInterface;
use Draw\Component\Tester\Http\TestResponse;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends TestCase
{
    public function testConstruct()
    {
        /** @var RequestExecutionerInterface|MockObject $requestExecutioner */
        $requestExecutioner = $this->getMockBuilder(RequestExecutionerInterface::class)
            ->setMethods(['executeRequest'])
            ->getMock();

        $requestExecutioner->method('executeRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                return new Response(
                    200,
                    [],
                    json_encode(
                        [
                            'method' => $request->getMethod(),
                            'uri' => $request->getUri()->__toString(),
                            'body' => $request->getBody()->getContents(),
                            'headers' => $request->getHeaders(),
                            'version' => $request->getProtocolVersion(),
                        ]
                    )
                );
            });

        $client = new Client($requestExecutioner);

        static::assertInstanceOf(ClientInterface::class, $client);

        return $client;
    }

    /**
     * @depends testConstruct
     */
    public function testGet(Client $client)
    {
        $testResponse = $client->get(
            $uri = '/test',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'GET',
            $uri,
            null,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testHead(Client $client)
    {
        $testResponse = $client->head(
            $uri = '/test',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'HEAD',
            $uri,
            null,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testPut(Client $client)
    {
        $testResponse = $client->put(
            $uri = '/test',
            $body = 'body',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'PUT',
            $uri,
            $body,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testPost(Client $client)
    {
        $testResponse = $client->post(
            $uri = '/test',
            $body = 'body',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'POST',
            $uri,
            $body,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testDelete(Client $client)
    {
        $testResponse = $client->delete(
            $uri = '/test',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'DELETE',
            $uri,
            null,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testOptions(Client $client)
    {
        $testResponse = $client->options(
            $uri = '/test',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'OPTIONS',
            $uri,
            null,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testPatch(Client $client)
    {
        $testResponse = $client->patch(
            $uri = '/test',
            $body = 'body',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidTestResponse(
            $testResponse,
            'PATCH',
            $uri,
            $body,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testSend(Client $client)
    {
        $request = new Request(
            $method = 'POST',
            $uri = '/test',
            $headers = ['header' => ['value']],
            $body = 'body',
            $version = '1.0'
        );

        $testResponse = $client->send($request);

        $this->assertValidTestResponse(
            $testResponse,
            $method,
            $uri,
            $body,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testCreateRequest(Client $client)
    {
        $request = $client->createRequest(
            $method = 'POST',
            $uri = '/test',
            $body = 'body',
            $headers = ['header' => ['value']],
            $version = '1.0'
        );

        $this->assertValidRequest(
            $request,
            $method,
            $uri,
            $body,
            $headers,
            $version
        );
    }

    /**
     * @depends testConstruct
     */
    public function testRegisterObserver(Client $client)
    {
        $mockClientObserver = $this->getMockBuilder(ClientObserver::class)
            ->setMethodsExcept([])
            ->getMockForAbstractClass();

        $mockClientObserver
            ->expects(static::once())
            ->method('preSendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                return $request;
            });

        $mockClientObserver
            ->expects(static::once())
            ->method('postSendRequest')
            ->willReturnCallback(function (RequestInterface $request, ResponseInterface $response) {
                return $response;
            });

        /* @var ClientObserver $mockClientObserver */
        $client->registerObserver($mockClientObserver);

        $client->send(new Request('GET', '/test'));
    }

    public function assertValidTestResponse(
        TestResponse $testResponse,
        $method,
        $uri,
        $body = null,
        array $headers = [],
        $version = '1.1'
    ) {
        $body = $body ?: '';
        $response = $testResponse->getResponse();

        static::assertInstanceOf(ResponseInterface::class, $response);

        // We seek at the beginning of the body to be sure that nobody change the position before
        $response->getBody()->seek(0);

        static::assertJsonStringEqualsJsonString(
            json_encode(compact('method', 'uri', 'body', 'headers', 'version')),
            $response->getBody()->getContents()
        );

        $this->assertValidRequest(
            $testResponse->getRequest(),
            $method,
            $uri,
            $body,
            $headers,
            $version
        );
    }

    public function assertValidRequest(
        RequestInterface $request,
        $method,
        $uri,
        $body = null,
        array $headers = [],
        $version = '1.1'
    ) {
        // We seek at the beginning of the body to be sure that nobody change the position before
        $request->getBody()->seek(0);

        static::assertSame($method, $request->getMethod());
        static::assertSame($uri, $request->getUri()->__toString());
        static::assertSame($body ?: '', $request->getBody()->getContents());

        foreach ($headers as $key => $values) {
            static::assertSame($values, $request->getHeader($key));
        }

        static::assertSame($version, $request->getProtocolVersion());
    }
}
