<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy\LegacyService;

final class ModernService
{
    public function __construct(private LegacyService $legacyService)
    {
    }

    public function legacyService(): LegacyService
    {
        return $this->legacyService;
    }
}
