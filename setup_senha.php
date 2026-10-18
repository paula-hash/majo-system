<?php
// Este ficheiro gera o hash correcto e actualiza a senha do admin
// APAGA ESTE FICHEIRO DEPOIS DE USAR!
require_once 'includes/conexao.php';

$nova_senha = 'Majo@2025#Adm';
$hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $conn->prepare("UPDATE utilizadores SET senha = ? WHERE email = 'admin@majo.com'");
$stmt->bind_param("s", $hash);
if ($stmt->execute()) {
    echo "<div style='font-family:sans-serif;padding:30px;background:#d1fae5;border-radius:10px;max-width:500px;margin:50px auto;'>
        <h2 style='color:#065f46'>✅ Senha actualizada com sucesso!</h2>
        <p><strong>Email:</strong> admin@majo.com</p>
        <p><strong>Nova senha:</strong> Majo@2025#Adm</p>
        <p style='color:#dc2626;font-weight:bold;'>⚠️ APAGA ESTE FICHEIRO AGORA! (setup_senha.php)</p>
        <a href='/majo_system/login.php' style='display:inline-block;margin-top:15px;padding:10px 20px;background:#1a3c5e;color:white;border-radius:8px;text-decoration:none;'>Ir para o Login</a>
    </div>";
} else {
    echo "Erro: " . $conn->error;
}
?>
