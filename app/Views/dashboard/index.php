<?php $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Painel<?= $this->endSection() ?>
<?= $this->section('sidebar_active') ?>dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="top-bar">
    <h1>Painel</h1>
    <form action="/logout" method="post" style="margin: 0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-danger">Sair</button>
    </form>
</div>

<div class="card">
    <h2>Bem-vindo, <?= esc(session()->get('username')) ?>!</h2>
    <p>Use a barra lateral para gerenciar landing pages e leads.</p>
</div>
<?= $this->endSection() ?>
