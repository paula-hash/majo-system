<?php
// ============================================================
// FICHEIRO: pages/historico.php
// FUNÇÃO: Ver o histórico de todas as ações no sistema
// ============================================================
require_once '../includes/sessao.php';
require_once '../includes/conexao.php';
verificarGerenteOuAdmin();;
verificarLogin();

// Só administrador
if (!in_array($_SESSION["perfil"], ["admin","gerente"])) {
    setMensagem("erro", "Acesso restrito ao Administrador.");
    header("Location: /majo_system/pages/produtos/listar.php");
    exit();
}

$historico = $conn->query("
    SELECT h.*, u.nome as utilizador_nome
    FROM historico h
    LEFT JOIN utilizadores u ON h.utilizador_id = u.id
    ORDER BY h.data_hora DESC
    LIMIT 100
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-history"></i> Histórico de Ações</div>
        <div class="page-subtitulo">Registo de todas as operações realizadas no sistema</div>
    </div>
</div>

<div class="card">
    <div class="tabela-container">
        <table>
            <thead>
                <tr><th>Data e Hora</th><th>Utilizador</th><th>Ação</th><th>Descrição</th></tr>
            </thead>
            <tbody>
                <?php while($h = $historico->fetch_assoc()):
                    $cores = ['CRIAR'=>'sucesso','EDITAR'=>'aviso','EXCLUIR'=>'inativo'];
                    $cor = $cores[$h['acao']] ?? 'ativo';
                ?>
                <tr>
                    <td style="color:var(--texto-claro); font-size:13px;">
                        <?= date('d/m/Y H:i:s', strtotime($h['data_hora'])) ?>
                    </td>
                    <td><strong><?= htmlspecialchars($h['utilizador_nome'] ?? 'Sistema') ?></strong></td>
                    <td><span class="badge badge-<?= $cor ?>"><?= $h['acao'] ?></span></td>
                    <td><?= htmlspecialchars($h['descricao']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
