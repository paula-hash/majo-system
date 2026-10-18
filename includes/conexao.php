<?php
// ============================================================
// FICHEIRO: conexao.php
// FUNÇÃO: Ligar o PHP ao banco de dados MySQL
// ============================================================

// --- CONFIGURAÇÕES DO BANCO DE DADOS ---
// Muda estes valores conforme o teu ambiente (XAMPP, etc.)
define('DB_HOST', 'localhost');      // Servidor (normalmente localhost)
define('DB_USER', 'root');           // Utilizador do MySQL (padrão no XAMPP: root)
define('DB_PASS', '');               // Senha (no XAMPP normalmente é vazia)
define('DB_NAME', 'majo_sistema');   // Nome do banco de dados

// --- CRIAR A CONEXÃO ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- VERIFICAR SE A CONEXÃO DEU CERTO ---
if ($conn->connect_error) {
    die("
        <div style='font-family:sans-serif; background:#fee; border:1px solid red; padding:20px; margin:20px; border-radius:8px;'>
            <h3>❌ Erro de Conexão</h3>
            <p>Não foi possível ligar ao banco de dados.</p>
            <p><strong>Erro:</strong> " . $conn->connect_error . "</p>
            <p>Verifica se o XAMPP está iniciado (Apache + MySQL)</p>
        </div>
    ");
}

// --- DEFINIR CHARSET PARA SUPORTAR ACENTOS ---
$conn->set_charset("utf8");
?>
