<?php

declare(strict_types=1);

namespace App\Entity;

interface OwnableInterface
{
    public function getOwner(): ?User;
}
