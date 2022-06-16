<?php

namespace Draw\Component\OpenApi\EventListener;

use Draw\Component\OpenApi\Configuration\Serialization;
use Draw\Component\OpenApi\Event\PreSerializerResponseEvent;
use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ResponseSerializerListener implements EventSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;

    private SerializationContextFactoryInterface $serializationContextFactory;

    private bool $serializeNull;

    private SerializerInterface $serializer;

    public static function getSubscribedEvents(): array
    {
        // Must be executed before SensioFrameworkExtraBundle's listener
        return [
            KernelEvents::VIEW => ['onKernelView', 30],
            KernelEvents::RESPONSE => ['onKernelResponse', 30],
        ];
    }

    public function __construct(
        SerializerInterface $serializer,
        SerializationContextFactoryInterface $serializationContextFactory,
        EventDispatcherInterface $eventDispatcher,
        bool $serializeNull
    ) {
        $this->serializationContextFactory = $serializationContextFactory;
        $this->serializer = $serializer;
        $this->serializeNull = $serializeNull;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();

        if ($result instanceof Response) {
            return;
        }

        switch ($requestFormat = $request->getRequestFormat()) {
            case 'json':
                break;
            default:
                return;
        }

        if (null === $result) {
            $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));

            return;
        }

        $context = $this->serializationContextFactory->createSerializationContext();
        $context->setSerializeNull($this->serializeNull);

        $serialization = $request->attributes->get('_draw_open_api_serialization');

        if ($serialization instanceof Serialization) {
            if ($version = $serialization->getSerializerVersion()) {
                $context->setVersion($version);
            }

            if ($groups = $serialization->getSerializerGroups()) {
                $context->setGroups($groups);
            }

            foreach ($serialization->getContextAttributes() as $key => $value) {
                $context->setAttribute($key, $value);
            }
        }

        $this->eventDispatcher->dispatch(new PreSerializerResponseEvent($result, $serialization, $context));

        $data = $this->serializer->serialize($result, $requestFormat, $context);
        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/'.$requestFormat], true);

        if ($serialization instanceof Serialization
            && $serialization->getStatusCode()
        ) {
            $response->setStatusCode($serialization->getStatusCode());
        }

        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $responseEvent): void
    {
        if ($responseHeaderBag = $responseEvent->getRequest()->attributes->get('_responseHeaderBag')) {
            if ($responseHeaderBag instanceof ResponseHeaderBag) {
                $responseEvent->getResponse()->headers->add($responseHeaderBag->allPreserveCase());
            }
        }
    }

    /**
     * @see ResponseHeaderBag::set
     *
     * @param mixed $values
     */
    public static function setResponseHeader(Request $request, string $key, $values, bool $replace = true): void
    {
        $responseHeaderBag = $request->attributes->get('_responseHeaderBag', new ResponseHeaderBag());
        if (!$responseHeaderBag instanceof ResponseHeaderBag) {
            throw new \RuntimeException('The current attribute value of [_responseHeaderBag] is invalid');
        }

        $responseHeaderBag->set($key, $values, $replace);
        $request->attributes->set('_responseHeaderBag', $responseHeaderBag);
    }
}
