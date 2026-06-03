<?php
$orderToggle = ($order ?? 'DESC') === 'DESC' ? 'ASC' : 'DESC';
?>
<?php $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Gerenciar Leads<?= $this->endSection() ?>
<?= $this->section('sidebar_active') ?>leads<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="top-bar">
    <h1>Gerenciar Leads</h1>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= esc(session()->getFlashdata('message')) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="filters">
        <form action="/leads" method="get" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="text" name="search" class="form-control" placeholder="Buscar nome ou e-mail..." value="<?= esc($search ?? '') ?>">
            <select name="landing_page" class="form-control">
                <option value="">Todas as Landing Pages</option>
                <?php foreach ($landingPages as $page): ?>
                    <option value="<?= $page['id'] ?>" <?= ($landingPageId ?? '') == $page['id'] ? 'selected' : '' ?>>
                        <?= esc($page['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/leads" class="btn" style="background-color: var(--hairline-on-dark); color: var(--text-primary);">Limpar</a>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => $orderToggle])) ?>">Nome</a></th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Origem</th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $orderToggle])) ?>">Status</a></th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $orderToggle])) ?>">Data de Captura</a></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= esc($lead['name']) ?></td>
                        <td><?= esc($lead['email']) ?></td>
                        <td><?= esc($lead['phone'] ?? '—') ?></td>
                        <td><?= esc($lead['landing_page_slug']) ?></td>
                        <td><?= esc($lead['status']) ?></td>
                        <td><?= esc(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">Nenhum lead encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
