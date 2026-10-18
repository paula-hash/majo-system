<?php
// ============================================================
// FICHEIRO: sessao.php — Segurança e controlo de sessão
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurações seguras de sessão
    ini_set('session.cookie_httponly', 1);   // Impede acesso JS ao cookie
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 7200); // 2 horas
    session_start();
}

// ── VERIFICAR LOGIN ──────────────────────────────────────────
function verificarLogin() {
    if (!isset($_SESSION['utilizador_id'])) {
        header("Location: /majo_system/login.php");
        exit();
    }
    // Expirar sessão após 2 horas de inactividade
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
        session_destroy();
        header("Location: /majo_system/login.php?expirou=1");
        exit();
    }
    // Actualizar timestamp de actividade
    $_SESSION['login_time'] = time();
}

// ── VERIFICAR ADMIN ──────────────────────────────────────────
// Usado para: excluir produtos, gerir utilizadores
function verificarAdmin() {
    verificarLogin();
    if ($_SESSION['perfil'] !== 'admin') {
        setMensagem('erro', 'Acesso restrito. Não tens permissão para aceder a esta página.');
        header("Location: /majo_system/pages/produtos/listar.php");
        exit();
    }
}

// ── VERIFICAR ADMIN OU GERENTE ───────────────────────────────
// Usado para: relatórios, histórico, categorias
function verificarGerenteOuAdmin() {
    verificarLogin();
    if (!in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
        setMensagem('erro', 'Acesso restrito. Não tens permissão para aceder a esta página.');
        header("Location: /majo_system/pages/produtos/listar.php");
        exit();
    }
}

// ── VERIFICAR SE TEM DETERMINADO PERFIL ─────────────────────
// Usado nos templates para mostrar/esconder botões e menus
// Exemplo: temPerfil('admin','gerente')
function temPerfil(...$perfis) {
    return in_array($_SESSION['perfil'] ?? '', $perfis);
}

// ── HISTÓRICO DE AUDITORIA ───────────────────────────────────
function registarHistorico($conn, $acao, $tabela, $registo_id, $descricao) {
    $uid  = $_SESSION['utilizador_id'] ?? null;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $desc = $descricao . ' [IP: ' . $ip . ']';
    $stmt = $conn->prepare("INSERT INTO historico (utilizador_id, acao, tabela, registo_id, descricao) VALUES (?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("issis", $uid, $acao, $tabela, $registo_id, $desc);
        $stmt->execute();
    }
}

// ── MARGEM DE LUCRO ──────────────────────────────────────────
function calcularMargem($preco_compra, $preco_venda) {
    if ($preco_compra <= 0) return 0;
    return round((($preco_venda - $preco_compra) / $preco_compra) * 100, 2);
}

// ── CÓDIGO AUTOMÁTICO DE PRODUTO ─────────────────────────────
function gerarCodigo($conn) {
    $r = $conn->query("SELECT MAX(id) as mx FROM produtos");
    $row = $r->fetch_assoc();
    return 'PRD' . str_pad(($row['mx'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
}

// ── FORMATAR PREÇO ───────────────────────────────────────────
function formatarPreco($valor) {
    return number_format((float)$valor, 2, ',', '.') . ' Kz';
}

// ── MENSAGENS FLASH ──────────────────────────────────────────
function setMensagem($tipo, $texto) {
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
}
function getMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $m = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return $m;
    }
    return null;
}

// ── LIMPAR INPUT (segurança) ─────────────────────────────────
function limpar($texto) {
    return htmlspecialchars(trim(strip_tags($texto)), ENT_QUOTES, 'UTF-8');
}