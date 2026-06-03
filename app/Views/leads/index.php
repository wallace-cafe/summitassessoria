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
                    <th style="text-align: right;">Ações</th>
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
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn btn-sm btn-secondary js-view-message"
                                        data-name="<?= esc($lead['name'], 'attr') ?>"
                                        data-message="<?= esc($lead['message'] ?? '', 'attr') ?>">Mensagem</button>
                                <form action="/leads/archive/<?= (int) $lead['id'] ?>" method="post"
                                      onsubmit="return confirm('Arquivar este lead? Ele sai da lista e da API, mas continua no banco de dados.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger">Arquivar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">Nenhum lead encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: mensagem do lead -->
<div id="message-modal" class="modal-overlay" hidden>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="message-modal-title">
        <h2 id="message-modal-title" class="modal-title">Mensagem do lead</h2>
        <p id="message-modal-subtitle" class="modal-subtitle"></p>
        <div id="message-modal-body" class="modal-body"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="message-modal-close">Fechar</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal    = document.getElementById('message-modal');
    var body     = document.getElementById('message-modal-body');
    var subtitle = document.getElementById('message-modal-subtitle');
    var closeBtn = document.getElementById('message-modal-close');

    function openModal(name, message) {
        subtitle.textContent = name ? ('De: ' + name) : '';
        body.textContent = (message && message.trim() !== '') ? message : 'Nenhuma mensagem informada.';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-view-message').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(this.getAttribute('data-name'), this.getAttribute('data-message'));
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
<?= $this->endSection() ?>
