<?php

if (! function_exists('parse_block_delimiters')) {
    function parse_block_delimiters(string $html): array
    {
        $blocks = [];

        $startComment = '';
        $endComment = '';
        $blockNum = 1;

        while (true) {
            $startComment = "<!-- BLOCO_{$blockNum}_INICIO -->";
            $endComment   = "<!-- BLOCO_{$blockNum}_FIM -->";

            $startPos = strpos($html, $startComment);
            $endPos   = strpos($html, $endComment);

            if ($startPos === false || $endPos === false) {
                break;
            }

            $contentStart = $startPos + strlen($startComment);
            $contentEnd   = $endPos;
            $blockHtml = substr($html, $contentStart, $contentEnd - $contentStart);

            // Extract text content for backward compatibility
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $blockHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($dom);
            $body = $dom->getElementsByTagName('body')->item(0);
            $text = $body ? trim($body->textContent) : trim($blockHtml);

            $blocks[] = [
                'number' => $blockNum,
                'start'  => $contentStart,
                'end'    => $contentEnd,
                'text'   => $text,
                'html'   => $blockHtml, // Add raw HTML for parsing elements
            ];

            $blockNum++;
        }

        return $blocks;
    }
}

if (! function_exists('parse_block_elements')) {
    /**
     * Parse elements from block HTML content.
     * For headings and paragraphs, preserves inner HTML (br, em, strong, etc.).
     */
    function parse_block_elements(string $blockHtml): array
    {
        $elements = [];

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $blockHtml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DOMXPath($dom);

        // Helper: extract innerHTML of a DOMNode
        $innerHTML = function (DOMNode $node) use ($dom): string {
            $html = '';
            foreach ($node->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
            return $html;
        };

        // Extract <img> elements
        $imgs = $xpath->query('//img');
        foreach ($imgs as $img) {
            $elements[] = [
                'type' => 'img',
                'src'  => $img->getAttribute('src') ?? '',
                'alt'  => $img->getAttribute('alt') ?? '',
            ];
        }

        // Extract heading elements (h1-h6) — preserves inner HTML
        for ($i = 1; $i <= 6; $i++) {
            $headings = $xpath->query("//h{$i}");
            foreach ($headings as $h) {
                $elements[] = [
                    'type' => "h{$i}",
                    'text' => $innerHTML($h),
                ];
            }
        }

        // Extract <p> elements — preserves inner HTML
        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            $elements[] = [
                'type' => 'p',
                'text' => $innerHTML($p),
            ];
        }

        // Extract <a> elements
        $as = $xpath->query('//a');
        foreach ($as as $a) {
            $elements[] = [
                'type' => 'a',
                'href' => $a->getAttribute('href') ?? '',
                'text' => trim($a->textContent),
            ];
        }

        return $elements;
    }
}

if (! function_exists('update_block_elements')) {
    /**
     * Intelligently update elements in block HTML without breaking design.
     * Preserves all HTML attributes and structure; only updates content/attributes.
     * For headings and paragraphs, inner HTML (br, em, strong, etc.) is preserved.
     */
    function update_block_elements(string $blockHtml, array $updatedElements): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $blockHtml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath      = new DOMXPath($dom);
        $typeCounts = [];

        // Helper: replace all child nodes of $node with the parsed HTML from $innerHtml
        $setInnerHTML = function (DOMNode $node, string $innerHtml) use ($dom): void {
            // Remove existing children
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }

            if ($innerHtml === '') {
                return;
            }

            // Parse the new inner HTML in a temporary document
            $tmp = new DOMDocument();
            @$tmp->loadHTML(
                '<?xml encoding="UTF-8"><div>' . $innerHtml . '</div>',
                LIBXML_NOERROR | LIBXML_NOWARNING
            );

            $tmpDiv = $tmp->getElementsByTagName('div')->item(0);
            if (! $tmpDiv) {
                return;
            }

            foreach ($tmpDiv->childNodes as $child) {
                $imported = $dom->importNode($child, true);
                $node->appendChild($imported);
            }
        };

        foreach ($updatedElements as $elementData) {
            $type = $elementData['type'] ?? '';
            if ($type === '') {
                continue;
            }

            if (! isset($typeCounts[$type])) {
                $typeCounts[$type] = 0;
            }
            $targetIndex = $typeCounts[$type];
            $typeCounts[$type]++;

            if ($type === 'img') {
                $imgs = $xpath->query('//img');
                if ($imgs->length > $targetIndex) {
                    $img = $imgs->item($targetIndex);
                    if (isset($elementData['src'])) {
                        $img->setAttribute('src', $elementData['src']);
                    }
                    if (isset($elementData['alt'])) {
                        $img->setAttribute('alt', $elementData['alt']);
                    }
                }
            } elseif (preg_match('/^h[1-6]$/', $type)) {
                $headings = $xpath->query("//{$type}");
                if ($headings->length > $targetIndex) {
                    $setInnerHTML($headings->item($targetIndex), $elementData['text'] ?? '');
                }
            } elseif ($type === 'p') {
                $ps = $xpath->query('//p');
                if ($ps->length > $targetIndex) {
                    $setInnerHTML($ps->item($targetIndex), $elementData['text'] ?? '');
                }
            } elseif ($type === 'a') {
                $as = $xpath->query('//a');
                if ($as->length > $targetIndex) {
                    $a = $as->item($targetIndex);
                    if (isset($elementData['href'])) {
                        $a->setAttribute('href', $elementData['href']);
                    }
                    $a->textContent = $elementData['text'] ?? '';
                }
            }
        }

        // Extract body content
        $body   = $dom->getElementsByTagName('body')->item(0);
        $output = '';
        if ($body) {
            foreach ($body->childNodes as $node) {
                $output .= $dom->saveHTML($node);
            }
        }

        return trim($output);
    }
}

if (! function_exists('rebuild_block_html')) {
    /**
     * Rebuild HTML block from structured elements
     * Used when we want to create fresh HTML without preserving design
     */
    function rebuild_block_html(array $elements): string
    {
        $html = '';
        
        foreach ($elements as $element) {
            if ($element['type'] === 'img') {
                $src = htmlspecialchars($element['src'] ?? '', ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars($element['alt'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= "<img src=\"{$src}\" alt=\"{$alt}\">\n";
            } elseif (preg_match('/^h[1-6]$/', $element['type'])) {
                $tag = $element['type'];
                $text = htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= "<{$tag}>{$text}</{$tag}>\n";
            } elseif ($element['type'] === 'p') {
                $text = htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= "<p>{$text}</p>\n";
            } elseif ($element['type'] === 'a') {
                $href = htmlspecialchars($element['href'] ?? '', ENT_QUOTES, 'UTF-8');
                $text = htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= "<a href=\"{$href}\">{$text}</a>\n";
            }
        }
        
        return trim($html);
    }
}

if (! function_exists('inject_block_badges')) {
    /**
     * Inject visual badges and styles into HTML to mark block locations
     * Used for preview mode in edit page
     */
    function inject_block_badges(string $html): string
    {
        $blocks = parse_block_delimiters($html);
        
        // Replace in reverse order to maintain positions
        foreach (array_reverse($blocks) as $block) {
            $startComment = "<!-- BLOCO_{$block['number']}_INICIO -->";
            $endComment   = "<!-- BLOCO_{$block['number']}_FIM -->";
            
            $startPos = strpos($html, $startComment);
            $endPos   = strpos($html, $endComment);
            
            if ($startPos === false || $endPos === false) {
                continue;
            }
            
            $contentStart = $startPos + strlen($startComment);
            $blockContent = substr($html, $contentStart, $endPos - $contentStart);
            
            // Wrap block content with badge
            $wrappedContent = "\n<div class=\"block-badge\" data-block-number=\"{$block['number']}\" data-block-label=\"BLOCO {$block['number']}\">"
                . $blockContent
                . "</div>\n";
            
            $html = substr_replace($html, $wrappedContent, $contentStart, $endPos - $contentStart);
        }
        
        return $html;
    }
}

if (! function_exists('rewrite_block_content')) {
    function rewrite_block_content(string $html, array $blocks): string
    {
        $replacements = [];

        foreach ($blocks as $number => $newText) {
            $startComment = "<!-- BLOCO_{$number}_INICIO -->";
            $endComment   = "<!-- BLOCO_{$number}_FIM -->";

            $startPos = strpos($html, $startComment);
            $endPos   = strpos($html, $endComment);

            if ($startPos === false || $endPos === false) {
                continue;
            }

            $contentStart = $startPos + strlen($startComment);
            $contentEnd   = $endPos;

            $replacements[] = [
                'start' => $contentStart,
                'end'   => $contentEnd,
                'text'  => $newText,
            ];
        }

        usort($replacements, fn($a, $b) => $b['start'] <=> $a['start']);

        foreach ($replacements as $r) {
            $html = substr_replace($html, $r['text'], $r['start'], $r['end'] - $r['start']);
        }

        return $html;
    }
}
