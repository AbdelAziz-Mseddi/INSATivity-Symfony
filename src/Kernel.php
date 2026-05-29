<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        if (class_exists(\Doctrine\DBAL\Types\Type::class)) {
            \Doctrine\DBAL\Types\Type::overrideType('datetime', \Doctrine\DBAL\Types\VarDateTimeType::class);
            \Doctrine\DBAL\Types\Type::overrideType('datetimetz', \Doctrine\DBAL\Types\VarDateTimeType::class);
            \Doctrine\DBAL\Types\Type::overrideType('datetime_immutable', \Doctrine\DBAL\Types\VarDateTimeImmutableType::class);
            \Doctrine\DBAL\Types\Type::overrideType('datetimetz_immutable', \Doctrine\DBAL\Types\VarDateTimeImmutableType::class);
        }
    }
}
