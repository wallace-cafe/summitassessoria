<?php $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Login<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <img src="/assets/img/logotipo-summit.png" alt="Summit Assessoria" class="auth-logo">

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <form action="/summit-admin" method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="username">Usuário</label>
            <input type="text" name="username" id="username" class="form-control" value="<?= old('username') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
    </form>
</div>
<?= $this->endSection() ?>
