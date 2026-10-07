<?php

namespace Modules\Site\Support;

class YouTube
{
    /**
     * Extract the video id from any usual YouTube URL shape (watch?v=,
     * youtu.be/, shorts/, embed/, live/), or return null.
     */
    public static function id(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{6,}$/', $raw) && ! str_contains($raw, '.')) {
            return $raw;
        }

        $parts = parse_url($raw);
        if (! $parts || empty($parts['host'])) {
            return null;
        }
        $host = preg_replace('/^(www|m)\./', '', strtolower($parts['host']));
        $path = trim($parts['path'] ?? '', '/');

        if ($host === 'youtu.be') {
            return self::segment($path);
        }
        if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (! empty($query['v'])) {
                return self::segment((string) $query['v']);
            }
            foreach (['shorts/', 'embed/', 'live/', 'v/'] as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return self::segment(substr($path, strlen($prefix)));
                }
            }
        }

        return null;
    }

    private static function segment(string $path): ?string
    {
        $path = explode('/', trim($path, '/'))[0] ?? '';
        $path = explode('?', $path)[0];

        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $path) ? $path : null;
    }
}
