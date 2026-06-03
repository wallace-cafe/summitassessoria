<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title', true) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?= $this->renderSection('css') ?>
</head>
<body>
    <div class="auth-layout">
        <?= $this->renderSection('content') ?>
    </div>

    <?= $this->renderSection('js') ?>
</body>
</html>
