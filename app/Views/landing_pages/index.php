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
                            <div class="row-actions" style="justify-content: flex-start;">
                                <a href="/landing-pages/edit/<?= $page['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                <?php if (! empty($page['active'])): ?>
                                    <form action="/landing-pages/deactivate/<?= $page['id'] ?>" method="post"
                                          onsubmit="return confirm('Desativar esta landing page? Ela deixará de abrir publicamente.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form action="/landing-pages/activate/<?= $page['id'] ?>" method="post"
                                          onsubmit="return confirm('Ativar esta landing page novamente?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success">Ativar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
