<?php $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Nova Landing Page<?= $this->endSection() ?>
<?= $this->section('sidebar_active') ?>landing-pages<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="top-bar">
    <h1>Nova Landing Page</h1>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <div><?= esc($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <form action="/landing-pages" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="title">Título da Página</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= old('title') ?>" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" id="slug" class="form-control" value="<?= old('slug') ?>" required>
        </div>

        <div class="form-group">
            <label for="gtm_id">Google Tag Manager (GTM)</label>
            <div style="display: flex; align-items: center; gap: 0;">
                <span style="background: var(--hairline-on-dark); color: var(--text-primary); padding: 0.5rem 0.75rem; border: 1px solid #505050; border-right: none; border-radius: 4px 0 0 4px; font-weight: 600; font-size: 0.875rem;">GTM-</span>
                <input type="text" name="gtm_id" id="gtm_id" class="form-control" value="<?= old('gtm_id') ?>" placeholder="somente os dígitos (ex: ABC1234X)" style="border-radius: 0 4px 4px 0; flex: 1;" maxlength="15">
            </div>
            <small style="color: var(--text-secondary);">O ID será aplicado no index.html substituindo o padrão GTM-XXXXXX.</small>
        </div>

        <hr>

        <div class="form-group">
            <label for="index_html">index.html <small>(obrigatório)</small></label>
            <input type="file" name="index_html" id="index_html" class="form-control" accept=".html" required>
        </div>
        <div class="form-group">
            <label for="style_css">style.css <small>(opcional)</small></label>
            <input type="file" name="style_css" id="style_css" class="form-control" accept=".css">
        </div>
        <div class="form-group">
            <label for="app_js">app.js <small>(opcional)</small></label>
            <input type="file" name="app_js" id="app_js" class="form-control" accept=".js">
        </div>
        <div class="form-group">
            <label for="assets">Assets <small>(opcional, imagens ou vídeos até 50MB: .jpg, .jpeg, .png, .webp, .svg, .gif, .mp4, .webm, .ogg)</small></label>
            <input type="file" name="assets[]" id="assets" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg,.gif,.mp4,.webm,.ogg" multiple>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Página</button>
        <a href="/landing-pages" class="btn" style="margin-left: 0.5rem;">Cancelar</a>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var titleInput = document.getElementById('title');
    var slugInput = document.getElementById('slug');
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function () {
            if (!slugInput.dataset.userEdited) {
                slugInput.value = titleInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
        slugInput.addEventListener('input', function () {
            slugInput.dataset.userEdited = 'true';
        });
    }
});
</script>
<?= $this->endSection() ?>
