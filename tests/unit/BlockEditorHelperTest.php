<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockEditorHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('block_editor');
    }

    // --- parse_block_delimiters() tests ---

    public function testNoBlockCommentsReturnsEmptyArray(): void
    {
        $html = '<html><body><p>No blocks here</p></body></html>';
        $this->assertSame([], parse_block_delimiters($html));
    }

    public function testOneBlockExtractsTextContent(): void
    {
        $html = '<html><body><!-- BLOCO_1_INICIO -->Hello World<!-- BLOCO_1_FIM --></body></html>';
        $blocks = parse_block_delimiters($html);
        $this->assertCount(1, $blocks);
        $this->assertSame('Hello World', $blocks[0]['text']);
        $this->assertSame(1, $blocks[0]['number']);
    }

    public function testMultipleBlocksReturnedInDocumentOrder(): void
    {
        $html = '<html><body>
            <!-- BLOCO_1_INICIO -->First<!-- BLOCO_1_FIM -->
            <!-- BLOCO_2_INICIO -->Second<!-- BLOCO_2_FIM -->
            <!-- BLOCO_3_INICIO -->Third<!-- BLOCO_3_FIM -->
        </body></html>';
        $blocks = parse_block_delimiters($html);
        $this->assertCount(3, $blocks);
        $this->assertSame('First', $blocks[0]['text']);
        $this->assertSame('Second', $blocks[1]['text']);
        $this->assertSame('Third', $blocks[2]['text']);
    }

    public function testStripsHtmlTagsReturningOnlyText(): void
    {
        $html = '<html><body><!-- BLOCO_1_INICIO -->
            <h1>Title</h1>
            <p>Paragraph with <strong>bold</strong></p>
            <!-- BLOCO_1_FIM --></body></html>';
        $blocks = parse_block_delimiters($html);
        $this->assertCount(1, $blocks);
        $this->assertStringContainsString('Title', $blocks[0]['text']);
        $this->assertStringContainsString('Paragraph with', $blocks[0]['text']);
        $this->assertStringContainsString('bold', $blocks[0]['text']);
        $this->assertStringNotContainsString('<h1>', $blocks[0]['text']);
        $this->assertStringNotContainsString('<strong>', $blocks[0]['text']);
    }

    public function testEmptyBlockReturnsEmptyText(): void
    {
        $html = '<html><body><!-- BLOCO_1_INICIO --><!-- BLOCO_1_FIM --></body></html>';
        $blocks = parse_block_delimiters($html);
        $this->assertCount(1, $blocks);
        $this->assertSame('', $blocks[0]['text']);
    }

    // --- rewrite_block_content() tests ---

    public function testRewriteSingleBlock(): void
    {
        $html = '<html><body><!-- BLOCO_1_INICIO -->Old Text<!-- BLOCO_1_FIM --></body></html>';
        $result = rewrite_block_content($html, [1 => 'New Text']);
        $this->assertStringContainsString('New Text', $result);
        $this->assertStringNotContainsString('Old Text', $result);
    }

    public function testRewriteMultipleBlocksIndependently(): void
    {
        $html = '<html><body>
            <!-- BLOCO_1_INICIO -->First<!-- BLOCO_1_FIM -->
            <!-- BLOCO_2_INICIO -->Second<!-- BLOCO_2_FIM -->
            <!-- BLOCO_3_INICIO -->Third<!-- BLOCO_3_FIM -->
        </body></html>';
        $result = rewrite_block_content($html, [1 => 'Updated 1', 3 => 'Updated 3']);
        $this->assertStringContainsString('Updated 1', $result);
        $this->assertStringContainsString('Second', $result);
        $this->assertStringContainsString('Updated 3', $result);
        $this->assertStringNotContainsString('First', $result);
        $this->assertStringNotContainsString('Third', $result);
    }

    public function testRewriteLeavesHtmlOutsideBlocksUntouched(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body>
            <header>Static Header</header>
            <!-- BLOCO_1_INICIO -->Editable<!-- BLOCO_1_FIM -->
            <footer>Static Footer</footer>
        </body></html>';
        $result = rewrite_block_content($html, [1 => 'Updated Content']);
        $this->assertStringContainsString('Static Header', $result);
        $this->assertStringContainsString('Static Footer', $result);
        $this->assertStringContainsString('Updated Content', $result);
    }

    public function testRewriteIdentityNoChange(): void
    {
        $html = '<html><body><!-- BLOCO_1_INICIO -->Same Text<!-- BLOCO_1_FIM --></body></html>';
        $result = rewrite_block_content($html, [1 => 'Same Text']);
        $this->assertSame($html, $result);
    }

    public function testRewriteWithDifferentBlockNumbers(): void
    {
        $html = '<html><body>
            <!-- BLOCO_1_INICIO -->Block 1<!-- BLOCO_1_FIM -->
            <!-- BLOCO_10_INICIO -->Block 10<!-- BLOCO_10_FIM -->
        </body></html>';
        $result = rewrite_block_content($html, [1 => 'Updated 1', 10 => 'Updated 10']);
        $this->assertStringContainsString('Updated 1', $result);
        $this->assertStringContainsString('Updated 10', $result);
        $this->assertStringNotContainsString('Block 1', $result);
        $this->assertStringNotContainsString('Block 10', $result);
    }

    // --- Integration tests ---

    public function testFullRoundTripParseAndRewrite(): void
    {
        $original = '<html><body>
            <nav>Menu</nav>
            <!-- BLOCO_1_INICIO --><h1>Title</h1><p>Description</p><!-- BLOCO_1_FIM -->
            <section><!-- BLOCO_2_INICIO --><p>Middle content</p><!-- BLOCO_2_FIM --></section>
            <!-- BLOCO_3_INICIO --><footer>Footer text</footer><!-- BLOCO_3_FIM -->
        </body></html>';

        $blocks = parse_block_delimiters($original);
        $this->assertCount(3, $blocks);

        $modified = rewrite_block_content($original, [2 => 'Rewritten middle section']);

        $this->assertStringContainsString('Rewritten middle section', $modified);
        $this->assertStringContainsString('<h1>Title</h1>', $modified);
        $this->assertStringContainsString('Footer text', $modified);
        $this->assertStringContainsString('<nav>Menu</nav>', $modified);
        $this->assertStringContainsString('<!-- BLOCO_2_INICIO -->', $modified);
        $this->assertStringContainsString('<!-- BLOCO_2_FIM -->', $modified);
        $this->assertStringNotContainsString('<p>Middle content</p>', $modified);
    }

    // --- parse_block_elements() tests ---

    public function testParseBlockElementsExtractsAllTypes(): void
    {
        $blockHtml = '
            <h1>Heading 1</h1>
            <p>First paragraph</p>
            <img src="images/logo.png" alt="Company Logo">
            <h2>Heading 2</h2>
            <p>Second paragraph</p>
            <a href="https://example.com">Visit site</a>
        ';

        $elements = parse_block_elements($blockHtml);

        // Imgs are extracted first
        $this->assertCount(6, $elements);
        $this->assertSame('img', $elements[0]['type']);
        $this->assertSame('images/logo.png', $elements[0]['src']);
        $this->assertSame('Company Logo', $elements[0]['alt']);

        // Headings (h1 then h2)
        $this->assertSame('h1', $elements[1]['type']);
        $this->assertSame('Heading 1', $elements[1]['text']);

        $this->assertSame('h2', $elements[2]['type']);
        $this->assertSame('Heading 2', $elements[2]['text']);

        // Paragraphs
        $this->assertSame('p', $elements[3]['type']);
        $this->assertSame('First paragraph', $elements[3]['text']);

        $this->assertSame('p', $elements[4]['type']);
        $this->assertSame('Second paragraph', $elements[4]['text']);

        // Links
        $this->assertSame('a', $elements[5]['type']);
        $this->assertSame('https://example.com', $elements[5]['href']);
        $this->assertSame('Visit site', $elements[5]['text']);
    }

    // --- update_block_elements() tests ---

    public function testUpdateBlockElementsIntelligentlyUpdatesMultipleElements(): void
    {
        $blockHtml = '<div>
            <h1>Old Title</h1>
            <p>Old Paragraph 1</p>
            <p>Old Paragraph 2</p>
            <img src="old.png" alt="Old Alt">
            <a href="old-url">Old Link</a>
        </div>';

        // Elements in the exact same grouping/order as parse_block_elements:
        // 1. img
        // 2. h1
        // 3. p (x2)
        // 4. a
        $updatedElements = [
            [
                'type' => 'img',
                'src' => 'new.png',
                'alt' => 'New Alt',
            ],
            [
                'type' => 'h1',
                'text' => 'New Title',
            ],
            [
                'type' => 'p',
                'text' => 'New Paragraph 1',
            ],
            [
                'type' => 'p',
                'text' => 'New Paragraph 2',
            ],
            [
                'type' => 'a',
                'href' => 'new-url',
                'text' => 'New Link',
            ],
        ];

        $updatedHtml = update_block_elements($blockHtml, $updatedElements);

        $this->assertStringContainsString('<h1>New Title</h1>', $updatedHtml);
        $this->assertStringContainsString('<p>New Paragraph 1</p>', $updatedHtml);
        $this->assertStringContainsString('<p>New Paragraph 2</p>', $updatedHtml);
        $this->assertStringContainsString('<img src="new.png" alt="New Alt">', $updatedHtml);
        $this->assertStringContainsString('<a href="new-url">New Link</a>', $updatedHtml);

        $this->assertStringNotContainsString('Old Title', $updatedHtml);
        $this->assertStringNotContainsString('Old Paragraph 1', $updatedHtml);
        $this->assertStringNotContainsString('Old Paragraph 2', $updatedHtml);
        $this->assertStringNotContainsString('old.png', $updatedHtml);
        $this->assertStringNotContainsString('old-url', $updatedHtml);
    }
}
