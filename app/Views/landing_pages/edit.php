<?php $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Editar Landing Page<?= $this->endSection() ?>
<?= $this->section('sidebar_active') ?>landing-pages<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="top-bar">
    <h1>Editar: <?= esc($page['title']) ?></h1>
    <a href="/p/<?= esc($page['slug']) ?>" target="_blank" class="btn btn-sm btn-primary">Ver Página Pública</a>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <div><?= esc($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= esc(session()->getFlashdata('message')) ?>
    </div>
<?php endif; ?>

<?php
helper('block_editor');

$filePath = WRITEPATH . ($page['file_path'] ?? '') . '/index.html';
$rawHtml  = '';

if (is_file($filePath)) {
    $rawHtml = file_get_contents($filePath);
}

$blocks = $rawHtml !== '' ? parse_block_delimiters($rawHtml) : [];
$hasBlocks = count($blocks) > 0;
?>

<style>
    .edit-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        height: calc(100vh - 200px);
    }
    
    .edit-left {
        overflow-y: auto;
        padding-right: 1rem;
    }
    
    .edit-right {
        display: flex;
        flex-direction: column;
        border: 1px solid #404040;
        border-radius: 8px;
        background: #1a1a1a;
        padding: 0;
        overflow: hidden;
    }
    
    .preview-header {
        background: #1e1e1e;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #404040;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        color: #fff;
    }
    
    .preview-btn {
        background: #2d2d2d;
        color: #ddd;
        border: 1px solid #505050;
        padding: 0.35rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .preview-btn:hover {
        background: #3d3d3d;
        color: #fff;
        border-color: #666;
    }
    
    .preview-btn.active {
        background: #0ea5e9;
        color: #fff;
        border-color: #0ea5e9;
    }
    
    .preview-container {
        flex: 1;
        overflow: auto;
        background: #111;
        display: flex;
        justify-content: flex-start;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .edit-left .card {
        background-color: #2d2d2d;
        border: 1px solid #404040;
        color: #fff;
        margin-bottom: 1.5rem;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .card-header {
        padding: 1rem;
        background-color: #1a1a1a;
        border-bottom: 1px solid #404040;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    
    .card-header:hover {
        background-color: #222;
    }
    
    .chevron {
        font-size: 0.8rem;
        transition: transform 0.2s ease;
        color: #888;
    }
    
    .card.collapsed .chevron {
        transform: rotate(-90deg);
    }
    
    .card-body {
        padding: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .card.collapsed .card-body {
        display: none;
    }
    
    .edit-left .card label {
        color: #ddd;
        font-weight: 600;
    }
    
    .edit-left .form-control {
        background-color: #3d3d3d;
        color: #fff;
        border: 1px solid #505050;
    }
    
    .edit-left .form-control:focus {
        background-color: #3d3d3d;
        color: #fff;
        border-color: #0ea5e9;
    }
    
    .element-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 3px;
        font-size: 0.75rem;
        font-weight: bold;
        margin-right: 0.5rem;
    }
    
    .element-badge.img { background: #e8f4f8; color: #1e293b; }
    .element-badge.p { background: #f0e8f4; color: #1e293b; }
    .element-badge.a { background: #f4f0e8; color: #1e293b; }
    .element-badge.h { background: #fef3c7; color: #1e293b; }
    
    .preview-frame {
        width: 100%;
        height: 100%;
        border: none;
        background: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        transition: width 0.3s ease, height 0.3s ease;
        flex-shrink: 0;
        margin: 0 auto;
    }
    
    .form-buttons {
        margin-top: 2rem;
        position: sticky;
        bottom: 0;
        background: #1e2329;
        padding: 1rem;
        border-top: 1px solid #404040;
        display: flex;
        gap: 0.5rem;
        z-index: 5;
    }
</style>

<div class="edit-container">
    <!-- LEFT PANEL: FORM -->
    <div class="edit-left">
        <form action="/landing-pages/update/<?= $page['id'] ?>" method="post" id="edit-form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="file_unchanged" value="1">
            <input type="hidden" name="title" value="<?= esc($page['title']) ?>">

            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="padding: 1.5rem;">
                    <div class="form-group">
                        <label for="gtm_id">Google Tag Manager (GTM)</label>
                        <div style="display: flex; align-items: center; gap: 0;">
                            <span style="background: #3d3d3d; color: #ddd; padding: 0.5rem 0.75rem; border: 1px solid #505050; border-right: none; border-radius: 4px 0 0 4px; font-weight: 600; font-size: 0.875rem;">GTM-</span>
                            <input type="text" name="gtm_id" id="gtm_id" class="form-control" value="<?= esc(ltrim(str_replace('GTM-', '', $page['gtm_id'] ?? ''), '-')) ?>" placeholder="somente os dígitos (ex: ABC1234X)" style="border-radius: 0 4px 4px 0; flex: 1;" maxlength="15">
                        </div>
                        <small style="color: var(--text-secondary);">O ID será aplicado no index.html substituindo o padrão GTM-XXXXXX.</small>
                    </div>
                </div>
            </div>

            <?php if (! $hasBlocks && $rawHtml === ''): ?>
                <div class="alert alert-warning">
                    Arquivo da página não encontrado no disco.
                </div>
            <?php elseif (! $hasBlocks): ?>
                <div class="alert alert-warning">
                    Nenhum bloco editável detectado. Edite o HTML bruto abaixo.
                </div>
            <?php endif; ?>

            <?php if ($hasBlocks): ?>
                <?php foreach ($blocks as $block): ?>
                    <div class="card collapsed" id="card-block-<?= $block['number'] ?>">
                        <div class="card-header" onclick="toggleCard(<?= $block['number'] ?>)">
                            <strong>Bloco <?= $block['number'] ?></strong>
                            <span class="chevron">▼</span>
                        </div>
                        
                        <?php
                        $blockContent = isset($block['html']) ? $block['html'] : $block['text'];
                        $elements = parse_block_elements($blockContent);
                        ?>
                        
                        <div class="card-body">
                            <?php if (count($elements) === 0): ?>
                                <p style="color: #999; font-size: 0.9rem;">Nenhum elemento editável encontrado.</p>
                                <textarea name="block_raw_<?= $block['number'] ?>[]" class="form-control" rows="4"><?= esc($block['text']) ?></textarea>
                            <?php else: ?>
                                <?php foreach ($elements as $index => $element): ?>
                                    <div style="margin-bottom: 1rem; padding: 1rem; background-color: #3d3d3d; border: 1px solid #505050; border-radius: 4px;">
                                        
                                        <?php if ($element['type'] === 'img'): ?>
                                            <div style="margin-bottom: 0.75rem;">
                                                <span class="element-badge img">IMG</span>
                                            </div>

                                            <div style="margin-bottom: 0.75rem; text-align: center; background: #1a1a1a; border-radius: 4px; padding: 0.5rem;">
                                                <img src="/p/<?= esc($page['slug']) ?>/<?= esc($element['src']) ?>"
                                                     alt="<?= esc($element['alt']) ?>"
                                                     style="max-width: 100%; max-height: 120px; object-fit: contain;">
                                            </div>

                                            <div class="form-group">
                                                <label for="upload_img_<?= $block['number'] ?>_<?= $index ?>">
                                                    Substituir imagem
                                                </label>
                                                <input type="file"
                                                    name="upload_img_<?= $block['number'] ?>_<?= $index ?>"
                                                    id="upload_img_<?= $block['number'] ?>_<?= $index ?>"
                                                    class="form-control"
                                                    accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                                                    style="padding: 0.35rem;">
                                                <input type="hidden"
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][src_original]"
                                                    value="<?= esc($element['src']) ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="block_<?= $block['number'] ?>_img_<?= $index ?>_alt">
                                                    ALT (Texto alternativo)
                                                </label>
                                                <input type="text"
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][alt]"
                                                    id="block_<?= $block['number'] ?>_img_<?= $index ?>_alt"
                                                    class="form-control"
                                                    value="<?= esc($element['alt']) ?>">
                                            </div>

                                            <input type="hidden"
                                                name="block_<?= $block['number'] ?>_elements[<?= $index ?>][type]"
                                                value="img">
                                        
                                        <?php elseif (preg_match('/^h[1-6]$/', $element['type'])): ?>
                                            <div style="margin-bottom: 0.75rem;">
                                                <span class="element-badge h"><?= strtoupper($element['type']) ?></span>
                                                <small style="color:#aaa; font-size:0.72rem;">Pode usar &lt;br/&gt;, &lt;em&gt;, &lt;strong&gt;, etc.</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="block_<?= $block['number'] ?>_<?= $element['type'] ?>_<?= $index ?>_text">
                                                    Conteúdo
                                                </label>
                                                <textarea
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][text]"
                                                    id="block_<?= $block['number'] ?>_<?= $element['type'] ?>_<?= $index ?>_text"
                                                    class="form-control"
                                                    rows="3"><?= htmlspecialchars($element['text'], ENT_NOQUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                            
                                            <input type="hidden"
                                                name="block_<?= $block['number'] ?>_elements[<?= $index ?>][type]"
                                                value="<?= $element['type'] ?>">
                                        
                                        <?php elseif ($element['type'] === 'p'): ?>
                                            <div style="margin-bottom: 0.75rem;">
                                                <span class="element-badge p">P</span>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="block_<?= $block['number'] ?>_p_<?= $index ?>_text">
                                                    Parágrafo
                                                </label>
                                                <textarea 
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][text]" 
                                                    id="block_<?= $block['number'] ?>_p_<?= $index ?>_text"
                                                    class="form-control" 
                                                    rows="3"><?= esc($element['text']) ?></textarea>
                                            </div>
                                            
                                            <input type="hidden" 
                                                name="block_<?= $block['number'] ?>_elements[<?= $index ?>][type]" 
                                                value="p">
                                        
                                        <?php elseif ($element['type'] === 'a'): ?>
                                            <div style="margin-bottom: 0.75rem;">
                                                <span class="element-badge a">A</span>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="block_<?= $block['number'] ?>_a_<?= $index ?>_href">
                                                    HREF
                                                </label>
                                                <input type="text" 
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][href]" 
                                                    id="block_<?= $block['number'] ?>_a_<?= $index ?>_href"
                                                    class="form-control" 
                                                    value="<?= esc($element['href']) ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="block_<?= $block['number'] ?>_a_<?= $index ?>_text">
                                                    Texto
                                                </label>
                                                <input type="text" 
                                                    name="block_<?= $block['number'] ?>_elements[<?= $index ?>][text]" 
                                                    id="block_<?= $block['number'] ?>_a_<?= $index ?>_text"
                                                    class="form-control" 
                                                    value="<?= esc($element['text']) ?>">
                                            </div>
                                            
                                            <input type="hidden" 
                                                name="block_<?= $block['number'] ?>_elements[<?= $index ?>][type]" 
                                                value="a">
                                        <?php endif; ?>
                                    </div>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </div>
                      </div>
                  <?php endforeach; ?>
              <?php elseif ($rawHtml !== ''): ?>
                  <div class="form-group">
                      <label for="raw_html"><strong>HTML Bruto</strong></label>
                      <textarea name="raw_html" id="raw_html" class="form-control"
                          rows="20"><?= esc($rawHtml) ?></textarea>
                  </div>
              <?php endif; ?>
  
              <div class="form-buttons">
                  <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                  <a href="/landing-pages" class="btn" style="margin-left: 0.5rem; background: #3d3d3d; color: #fff;">Cancelar</a>
              </div>
          </form>
      </div>
  
      <!-- RIGHT PANEL: PREVIEW -->
      <div class="edit-right">
          <div class="preview-header">
              <span style="font-weight: 600; font-size: 0.85rem; color: #ddd;">Resolução do Preview</span>
              <div style="display: flex; gap: 0.5rem;" id="resolution-buttons">
                  <button type="button" class="preview-btn active" onclick="setPreviewSize('auto', this)">Ajustar Automático</button>
                  <button type="button" class="preview-btn" onclick="setPreviewSize('1366x768', this)">1366 x 768</button>
                  <button type="button" class="preview-btn" onclick="setPreviewSize('1920x1080', this)">1920 x 1080</button>
                  <button type="button" class="preview-btn" onclick="setPreviewSize('375x812', this)">Celular (375x812)</button>
              </div>
          </div>
          <div class="preview-container">
              <iframe 
                  id="preview-frame" 
                  class="preview-frame"
                  src="/p/<?= esc($page['slug']) ?>?preview=1"
                  sandbox="allow-same-origin allow-scripts allow-popups"
                  title="Page Preview">
              </iframe>
          </div>
      </div>
  </div>
  
  <script>
      // Highlight blocks on preview when hovering over elements in the form
      document.addEventListener('DOMContentLoaded', function() {
          const formGroups = document.querySelectorAll('[id^="block_"]');
          const previewFrame = document.getElementById('preview-frame');
          
          formGroups.forEach(group => {
              group.addEventListener('focus', function() {
                  // Extract block number from id
                  const match = this.id.match(/^block_(\d+)_/);
                  if (match) {
                      const blockNum = match[1];
                      highlightBlockInPreview(blockNum);
                  }
              }, true);
              
              group.addEventListener('blur', function() {
                  removeHighlightFromPreview();
              }, true);
          });
          
          function highlightBlockInPreview(blockNum) {
              try {
                  const previewDoc = previewFrame.contentDocument;
                  if (previewDoc) {
                      const badge = previewDoc.querySelector(`[data-block-number="${blockNum}"]`);
                      if (badge) {
                          badge.style.boxShadow = '0 0 20px rgba(239, 68, 68, 0.5)';
                          badge.scrollIntoView({ behavior: 'smooth', block: 'center' });
                      }
                  }
              } catch (e) {
                  // Cross-origin restrictions
              }
          }
          
          function removeHighlightFromPreview() {
              try {
                  const previewDoc = previewFrame.contentDocument;
                  if (previewDoc) {
                      const badges = previewDoc.querySelectorAll('[data-block-number]');
                      badges.forEach(badge => {
                          badge.style.boxShadow = '';
                      });
                  }
              } catch (e) {
                  // Cross-origin restrictions
              }
          }
      });
  
      function toggleCard(blockNum) {
          const card = document.getElementById('card-block-' + blockNum);
          if (card) {
              card.classList.toggle('collapsed');
          }
      }
  
      function setPreviewSize(size, btn) {
          const frame = document.getElementById('preview-frame');
          
          // Remove active class from all buttons
          document.querySelectorAll('#resolution-buttons .preview-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
  
          if (size === 'auto') {
              frame.style.width = '100%';
              frame.style.height = '100%';
              frame.style.maxWidth = '100%';
              frame.style.maxHeight = '100%';
          } else {
              const [width, height] = size.split('x');
              frame.style.width = width + 'px';
              frame.style.height = height + 'px';
              frame.style.maxWidth = 'none';
              frame.style.maxHeight = 'none';
          }
      }
  </script>
  <?= $this->endSection() ?>
