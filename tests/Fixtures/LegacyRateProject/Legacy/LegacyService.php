<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy;

final class LegacyService
{
    public function __construct(private LegacyHelper $legacyHelper)
    {
    }

    public function legacyHelper(): LegacyHelper
    {
        return $this->legacyHelper;
    }
}
