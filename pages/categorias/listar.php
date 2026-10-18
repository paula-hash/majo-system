<?php
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarLogin();

// Gerente e admin podem gerir categorias
if (!in_array($_SESSION['perfil'], ['admin','gerente'])) {
    setMensagem('erro', 'Sem permissão para gerir categorias.');
    header("Location: /majo_system/pages/produtos/listar.php");
    exit();
}

// Excluir categoria
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id  = (int)$_GET['excluir'];
    $tem = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE categoria_id = $id")->fetch_assoc()['total'];
    if ($tem > 0) {
        $conn->query("UPDATE produtos SET categoria_id = NULL WHERE categoria_id = $id");
    }
    $cat = $conn->query("SELECT nome FROM categorias WHERE id = $id")->fetch_assoc();
    $conn->query("DELETE FROM categorias WHERE id = $id");
    registarHistorico($conn, 'EXCLUIR', 'categorias', $id, "Categoria '{$cat['nome']}' excluída");
    setMensagem('sucesso', "Categoria '{$cat['nome']}' excluída. Os seus produtos ficaram sem categoria.");
    header("Location: /majo_system/pages/categorias/listar.php");
    exit();
}

// Criar/Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $id_edit   = (int)($_POST['id_edit'] ?? 0);

    if (!empty($nome)) {
        if ($id_edit) {
            $stmt = $conn->prepare("UPDATE categorias SET nome=?, descricao=? WHERE id=?");
            $stmt->bind_param("ssi", $nome, $descricao, $id_edit);
            $stmt->execute();
            setMensagem('sucesso', "Categoria atualizada com sucesso!");
        } else {
            $stmt = $conn->prepare("INSERT INTO categorias (nome, descricao) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $descricao);
            $stmt->execute();
            setMensagem('sucesso', "Categoria criada com sucesso!");
        }
    } else {
        setMensagem('erro', 'O nome da categoria é obrigatório.');
    }
    header("Location: /majo_system/pages/categorias/listar.php");
    exit();
}

$categorias = $conn->query("
    SELECT c.*, COUNT(p.id) as total_produtos
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.id
    GROUP BY c.id ORDER BY c.nome
");

require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-tags"></i> Categorias</div>
        <div class="page-subtitulo">Gerir as categorias dos produtos</div>
    </div>
    <button onclick="abrirModalCategoria()" class="btn btn-acento">
        <i class="fas fa-plus"></i> Nova Categoria
    </button>
</div>

<div style="margin-bottom:16px;">
    <input type="text" id="pesquisa-rapida" class="input-pesquisa"
           style="max-width:320px;" placeholder="Pesquisar categoria...">
</div>

<div class="card">
    <div class="tabela-container">
        <table id="tabela-principal">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Nº de Produtos</th>
                    <th>Criada em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($cat = $categorias->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($cat['nome']) ?></strong></td>
                    <td style="color:var(--texto-claro);"><?= htmlspecialchars($cat['descricao'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $cat['total_produtos'] > 0 ? 'ativo' : 'inativo' ?>">
                            <?= $cat['total_produtos'] ?> produto(s)
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($cat['criado_em'])) ?></td>
                    <td>
                        <div style="display:flex; gap:6px;">

                            <!-- VER -->
                            <button onclick='verCategoria(<?= json_encode($cat) ?>)'
                                    class="btn btn-outline btn-sm btn-icone" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>

                            <!-- EDITAR -->
                            <button onclick='abrirModalCategoria(<?= json_encode($cat) ?>)'
                                    class="btn btn-primario btn-sm btn-icone" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>

                            <!-- EXCLUIR -->
                            <button onclick="confirmarExclusaoCategoria(<?= $cat['id'] ?>, '<?= addslashes($cat['nome']) ?>', <?= $cat['total_produtos'] ?>)"
                                    class="btn btn-perigo btn-sm btn-icone" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>

                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL VER -->
<div class="modal-overlay" id="modal-ver-categoria">
    <div class="modal" style="max-width:420px;">
        <div class="modal-titulo">
            <i class="fas fa-eye"></i> Detalhes da Categoria
        </div>
        <div id="conteudo-ver-categoria"></div>
        <div class="modal-acoes">
            <button onclick="fecharModal()" class="btn btn-outline">
                <i class="fas fa-times"></i> Fechar
            </button>
        </div>
    </div>
</div>

<!-- MODAL CRIAR/EDITAR -->
<div class="modal-overlay" id="modal-categoria">
    <div class="modal">
        <div class="modal-titulo" id="modal-cat-titulo">
            <i class="fas fa-tag"></i> Nova Categoria
        </div>
        <form method="POST">
            <input type="hidden" name="id_edit" id="campo-id-edit" value="0">
            <div class="form-grupo" style="margin-bottom:14px;">
                <label>Nome da Categoria *</label>
                <input type="text" name="nome" id="campo-nome" class="form-control" required>
            </div>
            <div class="form-grupo" style="margin-bottom:14px;">
                <label>Descrição</label>
                <textarea name="descricao" id="campo-descricao" class="form-control"></textarea>
            </div>
            <div class="modal-acoes">
                <button type="button" onclick="fecharModal()" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-acento">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR EXCLUSÃO -->
<div class="modal-overlay" id="modal-excluir-categoria">
    <div class="modal" style="max-width:440px;">
        <div class="modal-titulo" style="color:var(--perigo);">
            <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
        </div>
        <p>Tens a certeza que queres excluir a categoria <strong id="nome-cat-excluir"></strong>?</p>
        <p id="aviso-produtos-cat" style="display:none; margin-top:10px; padding:10px;
           background:#fef3c7; border-radius:8px; font-size:13px; color:#92400e;">
            <i class="fas fa-exclamation-circle"></i>
            Esta categoria tem produtos associados. Eles ficarão sem categoria mas
            <strong>não serão apagados</strong>.
        </p>
        <p style="color:var(--texto-claro); font-size:13px; margin-top:10px;">
            Esta acção não pode ser desfeita.
        </p>
        <div class="modal-acoes">
            <button onclick="fecharModal()" class="btn btn-outline">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <a id="link-excluir-cat" href="#" class="btn btn-perigo">
                <i class="fas fa-trash"></i> Sim, Excluir
            </a>
        </div>
    </div>
</div>

<script>
function abrirModalCategoria(cat) {
    fecharModal();
    document.getElementById('modal-categoria').classList.add('aberto');

    if (cat) {
        document.getElementById('modal-cat-titulo').innerHTML = '<i class="fas fa-edit"></i> Editar Categoria';
        document.getElementById('campo-id-edit').value   = cat.id;
        document.getElementById('campo-nome').value      = cat.nome;
        document.getElementById('campo-descricao').value = cat.descricao || '';
    } else {
        document.getElementById('modal-cat-titulo').innerHTML = '<i class="fas fa-tag"></i> Nova Categoria';
        document.getElementById('campo-id-edit').value   = 0;
        document.getElementById('campo-nome').value      = '';
        document.getElementById('campo-descricao').value = '';
    }
}

function verCategoria(cat) {
    fecharModal();
    document.getElementById('conteudo-ver-categoria').innerHTML = `
        <div style="display:grid; gap:12px; margin-top:12px;">
            <div style="background:var(--fundo); padding:12px; border-radius:8px;">
                <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; margin-bottom:4px;">Nome</div>
                <strong>${cat.nome}</strong>
            </div>
            <div style="background:var(--fundo); padding:12px; border-radius:8px;">
                <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; margin-bottom:4px;">Descrição</div>
                <span>${cat.descricao || 'Sem descrição'}</span>
            </div>
            <div style="background:var(--fundo); padding:12px; border-radius:8px;">
                <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; margin-bottom:4px;">Nº de Produtos</div>
                <strong>${cat.total_produtos} produto(s)</strong>
            </div>
            <div style="background:var(--fundo); padding:12px; border-radius:8px;">
                <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; margin-bottom:4px;">Criada em</div>
                <strong>${new Date(cat.criado_em).toLocaleDateString('pt-PT')}</strong>
            </div>
        </div>
    `;
    document.getElementById('modal-ver-categoria').classList.add('aberto');
}

function confirmarExclusaoCategoria(id, nome, totalProdutos) {
    fecharModal();

    // Preencher o modal com os dados da categoria
    document.getElementById('nome-cat-excluir').textContent = nome;
    document.getElementById('link-excluir-cat').href = '?excluir=' + id;

    // Mostrar aviso se tiver produtos associados
    const aviso = document.getElementById('aviso-produtos-cat');
    aviso.style.display = totalProdutos > 0 ? 'block' : 'none';

    // Abrir o modal de confirmação
    document.getElementById('modal-excluir-categoria').classList.add('aberto');
}

function fecharModal() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('aberto'));
}
</script>

<?php require_once '../../includes/footer.php'; ?>