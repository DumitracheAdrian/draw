<?php

namespace App\Tests\Messenger\DoctrineEnvelopeEntityReference\EventListener;

use App\Document\TestDocument;
use App\Entity\User;
use App\Message\NewTestDocumentMessage;
use App\Message\NewUserMessage;
use App\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Draw\Component\Core\Reflection\ReflectionAccessor;
use Draw\Component\Messenger\SerializerEventDispatcher\Event\PostEncodeEvent;
use Draw\Component\Messenger\SerializerEventDispatcher\Event\PreEncodeEvent;
use Draw\Contracts\Messenger\EnvelopeFinderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PropertyReferenceEncodingListenerTest extends TestCase
{
    private bool $preEncodeEventCalled = false;

    private bool $postEncodeEventCalled = false;

    private static string $email;

    private EnvelopeFinderInterface $envelopeFinder;

    protected function setUp(): void
    {
        $this->envelopeFinder = static::getContainer()->get(EnvelopeFinderInterface::class);
    }

    public function testSend(): void
    {
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $user = new User();
        $user->setEmail(self::$email = uniqid().'@example.com');

        $eventDispatcher->addListener(
            PreEncodeEvent::class,
            function (PreEncodeEvent $event): void {
                $message = $event->getEnvelope()->getMessage();
                if (!$message instanceof NewUserMessage) {
                    return;
                }

                $this->preEncodeEventCalled = true;

                static::assertNull(
                    ReflectionAccessor::getPropertyValue(
                        $message,
                        'user'
                    ),
                    'User property should be null at this point.'
                );
            },
            -1
        );

        $eventDispatcher->addListener(
            PostEncodeEvent::class,
            function (PostEncodeEvent $event) use ($user): void {
                $message = $event->getEnvelope()->getMessage();
                if (!$message instanceof NewUserMessage) {
                    return;
                }

                $this->postEncodeEventCalled = true;

                static::assertSame(
                    $user,
                    ReflectionAccessor::getPropertyValue(
                        $message,
                        'user'
                    ),
                    'User property should be restored at this point.'
                );
            },
            -1
        );

        $entityManager->persist($user);

        $entityManager->flush();

        static::assertTrue($this->preEncodeEventCalled);

        static::assertTrue($this->postEncodeEventCalled);
    }

    /**
     * @depends testSend
     */
    public function testLoad(): void
    {
        $envelope = $this->envelopeFinder->findByTags([self::$email])[0];

        $message = $envelope->getMessage();

        static::assertInstanceOf(NewUserMessage::class, $message);

        static::assertSame(
            self::$email,
            $message->getUser()->getEmail()
        );
    }

    public function testODM(): void
    {
        $testDocument = new TestDocument();

        $manager = static::getContainer()->get('doctrine_mongodb')->getManager();

        $manager->persist($testDocument);

        $manager->flush();

        $envelope = $this->envelopeFinder->findByTags([$testDocument->id])[0];

        $message = $envelope->getMessage();

        static::assertInstanceOf(NewTestDocumentMessage::class, $message);

        static::assertSame(
            $testDocument,
            $message->getTestDocument()
        );
    }
}
