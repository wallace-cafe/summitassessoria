<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="/assets/img/logotipo-summit.png" alt="Summit Assessoria" class="brand-logo brand-full">
            <img src="/assets/img/logotipo-summit.png" alt="Summit" class="brand-logo-icon brand-short">
        </div>
        <button type="button" id="sidebar-toggle" class="sidebar-toggle-btn" title="Alternar Barra Lateral">
            <i class="bx bx-menu"></i>
        </button>
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="/dashboard" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" title="Painel">
                <i class="bx bx-grid-alt nav-icon"></i>
                <span class="nav-text">Painel</span>
            </a>
        </li>
        <li>
            <a href="/landing-pages" class="<?= ($active ?? '') === 'landing-pages' ? 'active' : '' ?>" title="Landing Pages">
                <i class="bx bx-layout nav-icon"></i>
                <span class="nav-text">Landing Pages</span>
            </a>
        </li>
        <li>
            <a href="/leads" class="<?= ($active ?? '') === 'leads' ? 'active' : '' ?>" title="Gerenciar Leads">
                <i class="bx bx-group nav-icon"></i>
                <span class="nav-text">Gerenciar Leads</span>
            </a>
        </li>
    </ul>
</aside>
