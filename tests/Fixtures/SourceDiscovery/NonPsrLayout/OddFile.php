<?php

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace App\Domain;

abstract class ActualClass
{
    use ActualTrait;
}

interface ActualContract
{
}

trait ActualTrait
{
}

enum ActualStatus
{
    case Ready;
}
