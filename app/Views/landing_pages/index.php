<?php $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Landing Pages<?= $this->endSection() ?>
<?= $this->section('sidebar_active') ?>landing-pages<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="top-bar">
    <h1>Landing Pages</h1>
    <?php if (session('can_create_pages')): ?>
        <a href="/landing-pages/create" class="btn btn-primary">Criar Nova Página</a>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= esc(session()->getFlashdata('message')) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>URL Pública</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= esc($page['title']) ?></td>
                        <td><a href="/p/<?= esc($page['slug']) ?>" target="_blank">/p/<?= esc($page['slug']) ?></a></td>
                        <td><?= esc(date('d/m/Y H:i', strtotime($page['created_at']))) ?></td>
                        <td>
                            <a href="/landing-pages/edit/<?= $page['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="/landing-pages/delete/<?= $page['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary);">Nenhuma landing page ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
