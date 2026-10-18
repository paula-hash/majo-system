<?php
// ============================================================
// FICHEIRO: index.php (raiz do projeto)
// FUNÇÃO: Redirecionar para o login ou dashboard
// ============================================================
require_once 'includes/sessao.php';

if (isset($_SESSION['utilizador_id'])) {
    header("Location: /majo_system/pages/dashboard.php");
} else {
    header("Location: /majo_system/login.php");
}
exit();
?>
