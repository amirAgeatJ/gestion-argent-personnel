<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

#[AsEventListener(event: MessageEvent::class)]
final class MailerFromListener
{
    public function __construct(
        #[Autowire(env: 'MAILER_FROM')]
        private readonly string $defaultFrom,
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof Email && $message->getFrom() === []) {
            $message->from($this->defaultFrom);
        }
    }
}
