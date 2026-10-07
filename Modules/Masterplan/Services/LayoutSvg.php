<?php

namespace Modules\Masterplan\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Validation\ValidationException;

/**
 * An uploaded masterplan layout is an SVG in which every plot is a
 * `<g class="plan-plot">`. This normalises the file before it is stored: the
 * root gets a viewBox and matching width/height, every plot group gets the
 * key plot-N (document order) as its id and `data-plot`, and anything
 * that could run code is removed because the file is rendered inline.
 */
class LayoutSvg
{
    public const PLOT_CLASS = 'plan-plot';

    public const MAX_BYTES = 10 * 1024 * 1024;

    private const SVG_NS = 'http://www.w3.org/2000/svg';

    private const REMOVE_ELEMENTS = ['script', 'foreignObject', 'iframe', 'embed', 'object', 'handler'];

    /**
     * @return array{svg: string, width: int, height: int, keys: string[]}
     *
     * @throws ValidationException
     */
    public static function normalise(string $contents, string $field = 'file'): array
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([$field => $message]);

        if (strlen($contents) > self::MAX_BYTES) {
            $fail('The layout must be smaller than 10 MB.');
        }
        if (stripos($contents, '<!ENTITY') !== false) {
            $fail('The SVG must not declare entities.');
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($contents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->documentElement;
        if (! $loaded || ! $root || $root->localName !== 'svg' || $root->namespaceURI !== self::SVG_NS) {
            $fail('The layout must be an SVG file.');
        }

        self::sanitise($doc);
        [$width, $height] = self::ensureViewBox($root, $fail);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', self::SVG_NS);
        $groups = $xpath->query(sprintf("//svg:g[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", self::PLOT_CLASS));

        // Keys are ours: plot-N by document order, written into the group's id
        // (replacing any id the file had) and data-plot.
        $keys = [];
        foreach ($groups as $i => $group) {
            /** @var DOMElement $group */
            $key = 'plot-'.($i + 1);
            $group->setAttribute('id', $key);
            $group->setAttribute('data-plot', $key);
            $keys[] = $key;
        }

        if ($keys === []) {
            $fail('No plots found. Every plot in the SVG must be a <g class="plan-plot"> group.');
        }

        return [
            'svg' => $doc->saveXML($root),
            'width' => $width,
            'height' => $height,
            'keys' => $keys,
        ];
    }

    /**
     * Stamp each plot group with the id of its Plot row as `data-plot-id`
     * (and clear it where no row exists), so the stored file identifies its
     * plots on its own.
     *
     * @param  array<string, int>  $idsByKey  Plot ids keyed by svg_key
     */
    public static function withPlotIds(string $svg, array $idsByKey): string
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded || ! $doc->documentElement) {
            return $svg;
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', self::SVG_NS);
        foreach ($xpath->query('//svg:g[@data-plot]') as $group) {
            /** @var DOMElement $group */
            $id = $idsByKey[$group->getAttribute('data-plot')] ?? null;
            if ($id) {
                $group->setAttribute('data-plot-id', (string) $id);
            } else {
                $group->removeAttribute('data-plot-id');
            }
        }

        return $doc->saveXML($doc->documentElement);
    }

    private static function sanitise(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', self::SVG_NS);

        foreach (self::REMOVE_ELEMENTS as $name) {
            foreach (iterator_to_array($xpath->query("//*[local-name()='{$name}']")) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach ($xpath->query('//*') as $element) {
            /** @var DOMElement $element */
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->localName ?? $attribute->name);
                $value = strtolower(trim($attribute->value));
                if (str_starts_with($name, 'on')
                    || (in_array($name, ['href', 'src'], true) && (str_starts_with($value, 'javascript:') || str_starts_with($value, 'data:text/html')))) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }
    }

    /**
     * @return array{int, int}
     */
    private static function ensureViewBox(DOMElement $root, callable $fail): array
    {
        $box = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($box) === 4 && is_numeric($box[2]) && is_numeric($box[3]) && (float) $box[2] > 0 && (float) $box[3] > 0) {
            $width = (float) $box[2];
            $height = (float) $box[3];
        } else {
            $width = (float) preg_replace('/[^0-9.]/', '', $root->getAttribute('width'));
            $height = (float) preg_replace('/[^0-9.]/', '', $root->getAttribute('height'));
            if ($width <= 0 || $height <= 0) {
                $fail('The SVG needs a viewBox (or a width and height).');
            }
            $root->setAttribute('viewBox', "0 0 {$width} {$height}");
        }

        $width = (int) round($width);
        $height = (int) round($height);
        // Intrinsic size for <img> uses; the map scales it with CSS anyway.
        $root->setAttribute('width', (string) $width);
        $root->setAttribute('height', (string) $height);
        $root->setAttribute('class', trim($root->getAttribute('class').' plan-layout'));

        return [$width, $height];
    }
}
