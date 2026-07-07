<?php

declare(strict_types=1);

namespace App\Notifier;

use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

final class BudgetExceededNotification extends Notification implements EmailNotificationInterface
{
    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $email = (new Email())
            ->to($recipient->getEmail())
            ->subject($this->getSubject())
            ->text($this->getContent() ?: $this->getSubject());

        return new EmailMessage($email);
    }
}
