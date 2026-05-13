<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

final class ReportAssetInliner
{
    public function inline(string $indexPath): void
    {
        $html = file_get_contents($indexPath);
        if (!is_string($html)) {
            throw new \RuntimeException(sprintf('File "%s" can not be read', $indexPath));
        }

        $reportPath = dirname($indexPath);
        $html = $this->inlineStylesheets($html, $reportPath);
        $html = $this->inlineScripts($html, $reportPath);

        if (file_put_contents($indexPath, $html) === false) {
            throw new \RuntimeException(sprintf('File "%s" was not written', $indexPath));
        }
    }

    private function inlineStylesheets(string $html, string $reportPath): string
    {
        return (string) preg_replace_callback(
            '#\s*<link\b(?=[^>]*\brel=["\']stylesheet["\'])(?=[^>]*\bhref=["\']([^"\']+)["\'])[^>]*>\s*#i',
            function (array $matches) use ($reportPath): string {
                $href = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if (!$this->isLocalAsset($href)) {
                    return $matches[0];
                }

                $path = $this->resolveAssetPath($reportPath, $href);
                $css = file_get_contents($path);
                if (!is_string($css)) {
                    throw new \RuntimeException(sprintf('Report stylesheet "%s" can not be read', $path));
                }

                return PHP_EOL
                    . '    <style data-phpca-report-asset="' . $this->escapeAttribute($href) . '">'
                    . PHP_EOL
                    . $this->escapeStyleBody($css)
                    . PHP_EOL
                    . '    </style>'
                    . PHP_EOL;
            },
            $html
        );
    }

    private function inlineScripts(string $html, string $reportPath): string
    {
        $scripts = [];

        $html = (string) preg_replace_callback(
            '#\s*<script\b(?=[^>]*\bsrc=["\']([^"\']+)["\'])[^>]*>\s*</script>\s*#i',
            function (array $matches) use ($reportPath, &$scripts): string {
                $src = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if (!$this->isLocalAsset($src)) {
                    return $matches[0];
                }

                $path = $this->resolveAssetPath($reportPath, $src);
                $js = file_get_contents($path);
                if (!is_string($js)) {
                    throw new \RuntimeException(sprintf('Report script "%s" can not be read', $path));
                }

                $scripts[] = PHP_EOL
                    . '    <script data-phpca-report-asset="' . $this->escapeAttribute($src) . '">'
                    . PHP_EOL
                    . $this->escapeScriptBody($js)
                    . PHP_EOL
                    . '    </script>'
                    . PHP_EOL;

                return '';
            },
            $html
        );

        if ($scripts === []) {
            return $html;
        }

        $position = strripos($html, '</body>');
        if ($position === false) {
            throw new \RuntimeException('SPA report index.html can not be prepared for inline scripts.');
        }

        return substr($html, 0, $position) . implode('', $scripts) . substr($html, $position);
    }

    private function isLocalAsset(string $path): bool
    {
        return $path !== ''
            && strpos($path, 'data:') !== 0
            && strpos($path, 'http://') !== 0
            && strpos($path, 'https://') !== 0
            && strpos($path, '//') !== 0
            && strpos($path, '/') !== 0;
    }

    private function resolveAssetPath(string $reportPath, string $assetPath): string
    {
        $assetPathParts = preg_split('/[?#]/', $assetPath, 2);
        if (!is_array($assetPathParts)) {
            throw new \RuntimeException('Report asset path can not be resolved.');
        }

        $path = $reportPath . '/' . $assetPathParts[0];
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Report asset "%s" was not found', $path));
        }

        return $path;
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeScriptBody(string $value): string
    {
        return (string) preg_replace('#</script#i', '<\/script', $value);
    }

    private function escapeStyleBody(string $value): string
    {
        return (string) preg_replace('#</style#i', '<\/style', $value);
    }
}
