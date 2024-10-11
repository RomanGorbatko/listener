<?php

namespace App\Model\Uuid;

use Symfony\Component\Uid\Uuid;

interface UuidInterface
{
    public function getId(): Uuid;
}
