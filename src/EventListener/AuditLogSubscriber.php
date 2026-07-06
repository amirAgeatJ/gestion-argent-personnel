<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Account;
use App\Entity\AuditLog;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Trace la création/modification/suppression des comptes et transactions dans AuditLog.
 *
 * Doctrine ne permet pas de persister+flush une nouvelle entité directement dans
 * postPersist/postUpdate/postRemove (le UnitOfWork est déjà en cours de traitement) : on
 * bufferise donc les entrées et on ne les écrit qu'à postFlush, une fois le flush principal
 * terminé — c'est le pattern documenté par Doctrine pour ce cas d'usage.
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
final class AuditLogSubscriber
{
    /** @var list<array{action: string, entityType: string, entityId: string}> */
    private array $pending = [];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->record('created', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->record('updated', $args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->record('deleted', $args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pending === []) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $user = $this->security->getUser();

        foreach ($this->pending as $entry) {
            $log = new AuditLog();
            $log->setAction($entry['action']);
            $log->setEntityType($entry['entityType']);
            $log->setEntityId($entry['entityId']);

            if ($user instanceof User) {
                $log->setUser($user);
            }

            $entityManager->persist($log);
        }

        $this->pending = [];
        $entityManager->flush();
    }

    private function record(string $action, object $entity): void
    {
        if (!$entity instanceof Account && !$entity instanceof Transaction) {
            return;
        }

        $this->pending[] = [
            'action' => $action,
            'entityType' => (new \ReflectionClass($entity))->getShortName(),
            'entityId' => (string) $entity->getId(),
        ];
    }
}
