<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summit | <?= $this->renderSection('title', true) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(FCPATH . 'assets/css/style.css') ?>">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <?= $this->renderSection('css') ?>
</head>
<body>
    <script>
        (function() {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <div class="dashboard-layout">
        <?= view_cell('SidebarCell', ['active' => $this->renderSection('sidebar_active', true)]) ?>

        <div class="main-content">
            <?= $this->renderSection('content') ?>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <?= $this->renderSection('js') ?>
</body>
</html>
