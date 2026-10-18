<?php
// ============================================================
// FICHEIRO: logout.php
// FUNÇÃO: Terminar a sessão e voltar ao login
// ============================================================
require_once 'includes/sessao.php';

// Destruir todos os dados da sessão
session_destroy();

// Ir para o login
header("Location: /majo_system/login.php");
exit();
?>
