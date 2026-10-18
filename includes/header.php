<?php
// ============================================================
// FICHEIRO: header.php
// FUNÇÃO: Cabeçalho e menu de navegação (incluído em todas as páginas)
// ============================================================
$pagina_atual = basename($_SERVER['PHP_SELF']);
$msg = getMensagem();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO, LDA - Sistema de Produtos</title>
    <link rel="stylesheet" href="/majo_system/assets/css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>

<!-- BARRA LATERAL (Sidebar) -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-svg-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="42" height="42">
                <defs>
                    <linearGradient id="sg1" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#0f2744"/>
                        <stop offset="100%" style="stop-color:#1a3c5e"/>
                    </linearGradient>
                    <linearGradient id="sg2" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f59e0b"/>
                        <stop offset="100%" style="stop-color:#fbbf24"/>
                    </linearGradient>
                </defs>
                <rect x="4" y="4" width="112" height="112" rx="22" fill="url(#sg1)"/>
                <rect x="4" y="4" width="16" height="112" fill="url(#sg2)" opacity="0.35"/>
                <text x="62" y="82" font-family="Georgia,serif" font-weight="900"
                      font-size="72" fill="white" text-anchor="middle">M</text>
                <rect x="24" y="89" width="72" height="5" rx="2.5" fill="url(#sg2)"/>
            </svg>
        </div>
        <div class="logo-texto">
            <span class="logo-nome">MAJO</span>
            <span class="logo-sub">Sistema de Produtos</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="/majo_system/pages/dashboard.php" class="nav-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
            <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        <a href="/majo_system/pages/produtos/listar.php" class="nav-item <?= in_array($pagina_atual, ['listar.php','criar.php','editar.php','ver.php']) ? 'ativo' : '' ?>">
            <i class="fas fa-box"></i> <span>Produtos</span>
        </a>
        <a href="/majo_system/pages/categorias/listar.php" class="nav-item <?= $pagina_atual == 'listar.php' && strpos($_SERVER['PHP_SELF'], 'categorias') ? 'ativo' : '' ?>">
            <i class="fas fa-tags"></i> <span>Categorias</span>
        </a>
        <?php if (in_array($_SESSION['perfil'] ?? '', ['admin','gerente'])): ?>
        <a href="/majo_system/pages/relatorios.php" class="nav-item <?= $pagina_atual == 'relatorios.php' ? 'ativo' : '' ?>">
            <i class="fas fa-file-alt"></i> <span>Relatórios</span>
        </a>
        <a href="/majo_system/pages/historico.php" class="nav-item <?= $pagina_atual == 'historico.php' ? 'ativo' : '' ?>">
            <i class="fas fa-history"></i> <span>Histórico</span>
        </a>
        <?php endif; ?>
        <?php if (($_SESSION['perfil'] ?? '') === 'admin'): ?>
        <a href="/majo_system/pages/utilizadores/listar.php" class="nav-item <?= $pagina_atual == 'utilizadores.php' ? 'ativo' : '' ?>">
            <i class="fas fa-users"></i> <span>Utilizadores</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['nome'] ?? 'U', 0, 1)) ?></div>
            <div>
                <div class="user-nome"><?= htmlspecialchars($_SESSION['nome'] ?? '') ?></div>
                <div class="user-perfil"><?= ucfirst($_SESSION['perfil'] ?? '') ?></div>
            </div>
        </div>
        <a href="/majo_system/logout.php" class="btn-logout" title="Sair">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- CONTEÚDO PRINCIPAL -->
<div class="main-content">
    <!-- Botão para abrir/fechar sidebar no mobile -->
    <button class="btn-menu-mobile" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <?php if ($msg): ?>
    <div class="alerta alerta-<?= $msg['tipo'] ?>" id="alertaMsg">
        <i class="fas fa-<?= $msg['tipo'] == 'sucesso' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['texto']) ?>
        <button onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>
