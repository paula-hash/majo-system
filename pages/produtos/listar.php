<?php
// ============================================================
// FICHEIRO: pages/produtos/listar.php
// FUNÇÃO: Listar todos os produtos com pesquisa e filtros
// ============================================================
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarLogin();

// --- PROCESSAR EXCLUSÃO (só administrador pode excluir) ---
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    if ($_SESSION['perfil'] !== 'admin') {
        setMensagem('erro', 'Não tens permissão para excluir produtos.');
        header("Location: /majo_system/pages/produtos/listar.php");
        exit();
    }
    $id = (int)$_GET['excluir'];
    $p = $conn->query("SELECT nome, imagem FROM produtos WHERE id = $id")->fetch_assoc();
    if ($p) {
        if ($p['imagem'] && file_exists('../../uploads/produtos/' . $p['imagem'])) {
            unlink('../../uploads/produtos/' . $p['imagem']);
        }
        $conn->query("DELETE FROM produtos WHERE id = $id");
        registarHistorico($conn, 'EXCLUIR', 'produtos', $id, "Produto '{$p['nome']}' excluído");
        setMensagem('sucesso', "Produto '{$p['nome']}' excluído com sucesso!");
    }
    header("Location: /majo_system/pages/produtos/listar.php");
    exit();
}

// --- FILTROS E PESQUISA ---
$pesquisa      = trim($_GET['pesquisa'] ?? '');
$categoria     = (int)($_GET['categoria'] ?? 0);
$status_filtro = $_GET['status'] ?? '';
$pagina        = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina    = 10;
$offset        = ($pagina - 1) * $por_pagina;

$where  = "WHERE 1=1";
$params = [];
$tipos  = "";

if ($pesquisa) {
    $where .= " AND (p.nome LIKE ? OR p.codigo LIKE ? OR p.descricao LIKE ?)";
    $like = "%$pesquisa%";
    $params = array_merge($params, [$like, $like, $like]);
    $tipos .= "sss";
}
if ($categoria) {
    $where .= " AND p.categoria_id = ?";
    $params[] = $categoria;
    $tipos .= "i";
}
if ($status_filtro) {
    $where .= " AND p.status = ?";
    $params[] = $status_filtro;
    $tipos .= "s";
}

// Contar total para paginação
$sql_count  = "SELECT COUNT(*) as total FROM produtos p $where";
$stmt_count = $conn->prepare($sql_count);
if ($params) $stmt_count->bind_param($tipos, ...$params);
$stmt_count->execute();
$total_registos = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas  = ceil($total_registos / $por_pagina);

// Buscar produtos
$sql = "SELECT p.*, c.nome as categoria_nome
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        $where
        ORDER BY p.criado_em DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params_pagina = array_merge($params, [$por_pagina, $offset]);
$tipos_pagina  = $tipos . "ii";
$stmt->bind_param($tipos_pagina, ...$params_pagina);
$stmt->execute();
$produtos = $stmt->get_result();

// Categorias para o filtro
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome");

require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-box"></i> Produtos</div>
        <div class="page-subtitulo"><?= $total_registos ?> produto(s) encontrado(s)</div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php if (in_array($_SESSION['perfil'], ['admin','gerente'])): ?>
        <button onclick="exportarExcel('tabela-principal','Produtos_MAJO')" class="btn btn-exportar btn-sm">
            <i class="fas fa-file-excel"></i> Excel
        </button>
        <button onclick="exportarPDF('Lista de Produtos')" class="btn btn-exportar btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <?php endif; ?>
        <a href="criar.php" class="btn btn-acento">
            <i class="fas fa-plus"></i> Novo Produto
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="card" style="margin-bottom:20px; padding:16px;">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:200px;">
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Pesquisar</label>
            <input type="text" name="pesquisa" class="input-pesquisa" style="width:100%" id="pesquisa-rapida"
                   placeholder="Nome ou código do produto..."
                   value="<?= htmlspecialchars($pesquisa) ?>">
        </div>
        <div style="min-width:160px;">
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Categoria</label>
            <select name="categoria" class="form-control">
                <option value="">Todas</option>
                <?php while($cat = $categorias->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="min-width:130px;">
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Status</label>
            <select name="status" class="form-control">
                <option value="">Todos</option>
                <option value="ativo"   <?= $status_filtro == 'ativo'   ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $status_filtro == 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primario">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <a href="listar.php" class="btn btn-outline">Limpar</a>
    </form>
</div>

<!-- TABELA DE PRODUTOS -->
<div class="card">
    <div class="tabela-container">
        <table id="tabela-principal">
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço Venda</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($produtos->num_rows === 0): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:var(--texto-claro);">
                        <i class="fas fa-box-open" style="font-size:40px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                        Nenhum produto encontrado.
                    </td>
                </tr>
                <?php else: ?>
                <?php while($p = $produtos->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($p['imagem']): ?>
                            <img src="/majo_system/uploads/produtos/<?= htmlspecialchars($p['imagem']) ?>" class="img-produto-tabela" alt="">
                        <?php else: ?>
                            <div class="img-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><code style="background:var(--fundo); padding:3px 6px; border-radius:5px;"><?= htmlspecialchars($p['codigo']) ?></code></td>
                    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                    <td><?= htmlspecialchars($p['categoria_nome'] ?? 'Sem categoria') ?></td>
                    <td><?= formatarPreco($p['preco_venda']) ?></td>
                    <td>
                        <?php $cor = $p['quantidade_stock'] <= $p['stock_minimo'] ? 'var(--perigo)' : 'inherit'; ?>
                        <span style="color:<?= $cor ?>; font-weight:600;">
                            <?= $p['quantidade_stock'] ?>
                            <?php if ($p['quantidade_stock'] <= $p['stock_minimo']): ?>
                                <i class="fas fa-exclamation-triangle" title="Stock baixo!"></i>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td>
                        <div style="display:flex; gap:6px;">

                            <!-- Ver — todos os perfis -->
                            <a href="ver.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icone" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>

                            <!-- Editar — admin, gerente e operador -->
                            <?php if (in_array($_SESSION['perfil'], ['admin','gerente','operador'])): ?>
                            <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-primario btn-sm btn-icone" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>

                            <!-- Excluir — só admin -->
                            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                            <button onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= addslashes($p['nome']) ?>')"
                                    class="btn btn-perigo btn-sm btn-icone" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINAÇÃO -->
    <?php if ($total_paginas > 1): ?>
    <div class="paginacao">
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pesquisa=<?= urlencode($pesquisa) ?>&pagina=<?= $i ?>"
               class="<?= $i == $pagina ? 'atual' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO -->
<div class="modal-overlay" id="modal-excluir">
    <div class="modal">
        <div class="modal-titulo"><i class="fas fa-trash" style="color:var(--perigo)"></i> Confirmar Exclusão</div>
        <p>Tens a certeza que queres excluir o produto <strong id="nome-produto-excluir"></strong>?</p>
        <p style="color:var(--texto-claro); font-size:13px; margin-top:8px;">Esta ação não pode ser desfeita.</p>
        <div class="modal-acoes">
            <button onclick="fecharModal()" class="btn btn-outline">Cancelar</button>
            <a id="link-excluir" href="#" class="btn btn-perigo">
                <i class="fas fa-trash"></i> Sim, Excluir
            </a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>