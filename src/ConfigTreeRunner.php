<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Service\Config\EffectiveConfigNode;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportSuiteRenderer;

final class ConfigTreeRunner
{
    /**
     * @return array<string, string>
     */
    public function allowCurrentState(EffectiveConfigNode $rootNode): array
    {
        $savedPaths = [];
        foreach ($rootNode->flatten() as $node) {
            $stateStorage = $node->config()['exclusions']['allowed_state']['storage'] ?? null;
            if (!is_string($stateStorage) || $stateStorage === '') {
                continue;
            }

            (new PHPCleanArchitectureFacade($node->config()))->allowCurrentState($stateStorage);
            $savedPaths[$node->id()] = $stateStorage;
        }

        if ($savedPaths === []) {
            throw new \RuntimeException('Config "exclusions.allowed_state.storage" must not be empty!');
        }

        return $savedPaths;
    }

    /**
     * @param array<string> $allowedPaths
     *
     * @return array<string>
     */
    public function check(EffectiveConfigNode $rootNode, array $allowedPaths = []): array
    {
        $errors = [];
        foreach ($rootNode->flatten() as $node) {
            $nodeErrors = (new PHPCleanArchitectureFacade($node->config()))->check($allowedPaths);
            foreach ($nodeErrors as $error) {
                $errors[] = $node->id() === 'root' ? $error : '[' . $node->id() . '] ' . $error;
            }
        }

        return $errors;
    }

    /**
     * @param array<string> $allowedPaths
     */
    public function generateReports(EffectiveConfigNode $rootNode, array $allowedPaths = []): void
    {
        foreach ($rootNode->flatten() as $node) {
            (new PHPCleanArchitectureFacade($node->config()))->generateReport($node->reportPath(), $allowedPaths);
        }

        (new ReportSuiteRenderer())->render($rootNode);
    }
}
