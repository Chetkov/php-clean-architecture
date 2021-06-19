<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report;

interface TemplateRendererInterface
{
    /**
     * @param string $name Template name
     * @param array $variables Template variables
     * @return string
     */
    public function render(string $name, array $variables = []): string;
}
