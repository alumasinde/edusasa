<?php

declare(strict_types=1);

namespace App\Core;

final class ViewRenderer
{
    public static function render(string $view, array $data = []): string
    {
        $base = dirname(__DIR__, 2) . '/resources/views/';
        $file = $base . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        $layout = null;
        $content = self::capture($file, $data, $layout);
        if ($layout !== null) {
            $data['slot'] = $content;
            $content = self::capture($base . str_replace('.', '/', $layout) . '.php', $data, $ignored);
        }

        return self::injectAssets($content);
    }

    private static function capture(string $file, array $data, ?string &$layout): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private static function injectAssets(string $html): string
    {
        $css = '/assets/css/app.css?v=20';
        $js = '/assets/js/app.js?v=20';
        $tags = '<link rel="stylesheet" href="' . $css . '">' .
            '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">' .
            '<script src="' . $js . '" defer></script>';

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $tags . '</head>', $html, 1) ?? $html;
        }

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
            $tags . '</head><body>' . $html . '</body></html>';
    }
}
