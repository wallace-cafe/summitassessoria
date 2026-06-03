<?php

namespace Tests\unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ComposerCssTest extends CIUnitTestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();
        $this->css = file_get_contents(ROOTPATH . 'public/assets/css/style.css');
    }

    public function testNoComposerLayoutRule(): void
    {
        $this->assertStringNotContainsString('.composer-layout', $this->css);
    }

    public function testNoComposerEditorRule(): void
    {
        $this->assertStringNotContainsString('.composer-editor', $this->css);
    }

    public function testNoBlockInstanceRule(): void
    {
        $this->assertStringNotContainsString('.block-instance', $this->css);
    }

    public function testNoTokenInputsRule(): void
    {
        $this->assertStringNotContainsString('.token-inputs', $this->css);
    }

    public function testNoBlockPickerModalRule(): void
    {
        $this->assertStringNotContainsString('.block-picker-modal', $this->css);
    }

    public function testNoPreviewIframeRule(): void
    {
        $this->assertStringNotContainsString('.preview-iframe', $this->css);
    }

    public function testNoHeroEditorRule(): void
    {
        $this->assertStringNotContainsString('.hero-editor', $this->css);
    }

    public function testUsesCssCustomProperties(): void
    {
        $this->assertStringContainsString('var(--', $this->css);
    }

    public function testExistingDashboardClassesUnaffected(): void
    {
        $this->assertStringContainsString('.dashboard-layout', $this->css);
        $this->assertStringContainsString('.sidebar', $this->css);
        $this->assertStringContainsString('.data-table', $this->css);
        $this->assertStringContainsString('.card', $this->css);
        $this->assertStringContainsString('.btn', $this->css);
        $this->assertStringContainsString('.form-control', $this->css);
    }
}
