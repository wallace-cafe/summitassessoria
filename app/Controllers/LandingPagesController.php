<?php

namespace App\Controllers;

use App\Models\LandingPageModel;

class LandingPagesController extends BaseController
{
    public function index()
    {
        $model = new LandingPageModel();
        $data  = [
            'pages' => $model->orderBy('created_at', 'DESC')->findAll(),
        ];

        return view('landing_pages/index', $data);
    }

    public function create()
    {
        return view('landing_pages/create');
    }

    public function store()
    {
        // Validação de campos de texto via CodeIgniter
        $rules = [
            'title' => 'required|max_length[255]',
            'slug'  => 'required|max_length[255]|is_unique[landing_pages.slug]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validação manual de arquivos usando nome original (ext_in do CI usa guessExtension()
        // que resolve via MIME e falha para .js e arquivos rejeitados pelo php.ini)
        $fileErrors = [];

        // index.html — obrigatório
        $indexHtml = $this->request->getFile('index_html');
        if (! $indexHtml || $indexHtml->getError() === UPLOAD_ERR_NO_FILE) {
            $fileErrors['index_html'] = 'O arquivo index.html é obrigatório.';
        } elseif ($indexHtml->getError() === UPLOAD_ERR_INI_SIZE || $indexHtml->getError() === UPLOAD_ERR_FORM_SIZE) {
            $fileErrors['index_html'] = 'index.html excede o tamanho máximo permitido pelo servidor.';
        } elseif (strtolower($indexHtml->getClientExtension()) !== 'html') {
            $fileErrors['index_html'] = 'index.html deve ter extensão .html.';
        } elseif ($indexHtml->getSize() > 1024 * 1024) {
            $fileErrors['index_html'] = 'index.html não pode ultrapassar 1MB.';
        }

        // style.css — opcional
        $styleCss = $this->request->getFile('style_css');
        if ($styleCss && $styleCss->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($styleCss->getError() === UPLOAD_ERR_INI_SIZE || $styleCss->getError() === UPLOAD_ERR_FORM_SIZE) {
                $fileErrors['style_css'] = 'style.css excede o tamanho máximo permitido pelo servidor.';
            } elseif (strtolower($styleCss->getClientExtension()) !== 'css') {
                $fileErrors['style_css'] = 'style.css deve ter extensão .css.';
            } elseif ($styleCss->getSize() > 1024 * 1024) {
                $fileErrors['style_css'] = 'style.css não pode ultrapassar 1MB.';
            }
        }

        // app.js — opcional
        $appJs = $this->request->getFile('app_js');
        if ($appJs && $appJs->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($appJs->getError() === UPLOAD_ERR_INI_SIZE || $appJs->getError() === UPLOAD_ERR_FORM_SIZE) {
                $fileErrors['app_js'] = 'app.js excede o tamanho máximo permitido pelo servidor.';
            } elseif (strtolower($appJs->getClientExtension()) !== 'js') {
                $fileErrors['app_js'] = 'app.js deve ter extensão .js.';
            } elseif ($appJs->getSize() > 1024 * 1024) {
                $fileErrors['app_js'] = 'app.js não pode ultrapassar 1MB.';
            }
        }

        // assets[] — opcional, múltiplos (imagens e vídeos até 50MB)
        $allowedAssetExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'mp4', 'webm', 'ogg'];
        $assets = $this->request->getFileMultiple('assets');
        if ($assets) {
            foreach ($assets as $asset) {
                if (! $asset || $asset->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $assetName = $asset->getClientName();
                if ($asset->getError() === UPLOAD_ERR_INI_SIZE || $asset->getError() === UPLOAD_ERR_FORM_SIZE) {
                    $fileErrors['assets'] = "O arquivo \"{$assetName}\" excede o tamanho máximo permitido pelo servidor (verifique upload_max_filesize no php.ini).";
                    break;
                }
                $ext = strtolower($asset->getClientExtension());
                if (! in_array($ext, $allowedAssetExts, true)) {
                    $fileErrors['assets'] = "O arquivo \"{$assetName}\" tem extensão inválida. Permitidos: jpg, jpeg, png, webp, svg, gif, mp4, webm, ogg.";
                    break;
                }
                if ($asset->getSize() > 50 * 1024 * 1024) {
                    $fileErrors['assets'] = "O arquivo \"{$assetName}\" excede o limite de 50MB.";
                    break;
                }
            }
        }

        if (! empty($fileErrors)) {
            return redirect()->back()->withInput()->with('errors', $fileErrors);
        }

        $slug   = $this->request->getPost('slug');
        $dir    = WRITEPATH . 'landing_pages/' . $slug;
        $relDir = 'landing_pages/' . $slug;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $this->request->getFile('index_html');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $file->move($dir, 'index.html');
        }

        $css = $this->request->getFile('style_css');
        if ($css && $css->isValid() && ! $css->hasMoved()) {
            $css->move($dir, 'style.css');
        }

        $js = $this->request->getFile('app_js');
        if ($js && $js->isValid() && ! $js->hasMoved()) {
            $js->move($dir, 'app.js');
        }

        $assets = $this->request->getFileMultiple('assets');
        if ($assets) {
            $assetDir = $dir . '/assets';
            if (! is_dir($assetDir)) {
                mkdir($assetDir, 0755, true);
            }
            foreach ($assets as $asset) {
                if ($asset && $asset->isValid() && ! $asset->hasMoved()) {
                    $asset->move($assetDir, $asset->getName());
                }
            }
        }

        $indexPath = $dir . '/index.html';
        if (is_file($indexPath)) {
            $html = file_get_contents($indexPath);

            // Add action to form with id="contact-form" for lead submission
            $html = preg_replace(
                '/(<form\b[^>]*\bid\s*=\s*["\']contact-form["\'][^>]*)>/i',
                '$1 action="/p/' . $slug . '/lead">',
                $html,
                1
            );

            file_put_contents($indexPath, $html);

            if (! str_contains($html, '<form id="lead-form"')) {
                log_message('warning', '[landing_page.no_lead_form slug=' . $slug . ']');
            }
        }

        $gtmId = strip_tags($this->request->getPost('gtm_id'));
        if ($gtmId !== null && $gtmId !== '') {
            $gtmId = 'GTM-' . ltrim($gtmId, 'GTM-');
        }

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $this->request->getPost('title'),
            'slug'      => $slug,
            'file_path' => $relDir,
            'gtm_id'    => $gtmId ?: null,
        ]);

        log_message('info', "[landing_page.created slug={$slug} files=1+]");

        return redirect()->to('/landing-pages')->with('message', 'Landing page created successfully.');
    }

    public function edit($id)
    {
        $model = new LandingPageModel();
        $page  = $model->find($id);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('landing_pages/edit', ['page' => $page]);
    }

    public function update($id)
    {
        $model = new LandingPageModel();
        $page  = $model->find($id);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'title' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $filePath = WRITEPATH . ($page['file_path'] ?? '') . '/index.html';

        if (is_file($filePath)) {
            helper('block_editor');
            $html = file_get_contents($filePath);

            $blocks = parse_block_delimiters($html);
            $blockCount = 0;

            // Process each block and update elements intelligently
            foreach ($blocks as $block) {
                $blockNumber = $block['number'];
                $elementsKey = "block_{$blockNumber}_elements";
                
                $structuredElements = $this->request->getPost($elementsKey);
                
                if (is_array($structuredElements)) {
                    // Get the block's HTML
                    $blockContent = isset($block['html']) ? $block['html'] : $block['text'];
                    
                    // Prepare elements for update
                    $elements = [];
                    foreach ($structuredElements as $index => $elementData) {
                        if (isset($elementData['type'])) {
                            $srcOriginal = $elementData['src_original'] ?? '';
                            
                            // Handle image upload for img elements
                            if ($elementData['type'] === 'img' && $srcOriginal !== '') {
                                $uploadField = "upload_img_{$blockNumber}_{$index}";
                                
                                try {
                                    $file = $this->request->getFile($uploadField);
                                } catch (\Throwable $e) {
                                    $file = null;
                                }
                                
                                if ($file && $file->isValid() && ! $file->hasMoved()) {
                                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
                                    if (in_array($file->getMimeType(), $allowedTypes)) {
                                        $baseDir = WRITEPATH . ($page['file_path'] ?? '');
                                        $targetPath = realpath($baseDir . '/' . $srcOriginal);
                                        $basePath = realpath($baseDir);
                                        
                                        // Security check: ensure target is within landing page directory
                                        if ($targetPath && $basePath && str_starts_with($targetPath, $basePath) && is_file($targetPath)) {
                                            $file->move(dirname($targetPath), basename($targetPath), true);
                                        }
                                    }
                                }
                            }
                            
                            $el = [
                                'type' => $elementData['type'],
                                'alt' => $elementData['alt'] ?? '',
                                'href' => $elementData['href'] ?? '',
                                'text' => $elementData['text'] ?? '',
                            ];
                            if ($srcOriginal !== '') {
                                $el['src'] = $srcOriginal;
                            }
                            $elements[] = $el;
                        }
                    }
                    
                    // Update block with intelligent element updating
                    $updatedBlockHtml = update_block_elements($blockContent, $elements);
                    
                    // Replace in HTML
                    $startComment = "<!-- BLOCO_{$blockNumber}_INICIO -->";
                    $endComment   = "<!-- BLOCO_{$blockNumber}_FIM -->";
                    
                    $startPos = strpos($html, $startComment);
                    $endPos   = strpos($html, $endComment);
                    
                    if ($startPos !== false && $endPos !== false) {
                        $contentStart = $startPos + strlen($startComment);
                        $contentEnd   = $endPos;
                        $html = substr_replace($html, $updatedBlockHtml, $contentStart, $contentEnd - $contentStart);
                        $blockCount++;
                    }
                }
            }

            // Check for raw HTML fallback
            $rawHtml = $this->request->getPost('raw_html');
            if ($rawHtml !== null && $blockCount === 0) {
                $html = $rawHtml;
            }

            file_put_contents($filePath, $html);

            log_message('info', "[landing_page.updated slug={$page['slug']} blocks={$blockCount}]");
        }

        $gtmId = strip_tags($this->request->getPost('gtm_id'));
        if ($gtmId !== null && $gtmId !== '') {
            $gtmId = 'GTM-' . ltrim($gtmId, 'GTM-');
        }

        $model->update($id, [
            'title'  => $this->request->getPost('title'),
            'gtm_id' => $gtmId ?: null,
        ]);

        return redirect()->to('/landing-pages')->with('message', 'Landing page updated successfully.');
    }

    public function delete($id)
    {
        $model = new LandingPageModel();
        $page  = $model->find($id);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $dir = WRITEPATH . ($page['file_path'] ?? '');
        if (is_dir($dir)) {
            $fileCount = $this->removeDirectory($dir);
            log_message('info', "[landing_page.deleted slug={$page['slug']} files_removed={$fileCount}]");
        }

        $model->delete($id);

        return redirect()->to('/landing-pages')->with('message', 'Landing page deleted successfully.');
    }

    private function removeDirectory(string $dir): int
    {
        $count = 0;
        if (! is_dir($dir)) {
            return 0;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $count += $this->removeDirectory($path);
            } else {
                unlink($path);
                $count++;
            }
        }
        rmdir($dir);
        return $count;
    }
}
