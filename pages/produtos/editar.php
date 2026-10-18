<?php
// ============================================================
// FICHEIRO: pages/produtos/editar.php
// FUNÇÃO: Formulário para editar um produto existente
// ============================================================
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarLogin();

// Só administrador pode editar
if (!in_array($_SESSION["perfil"], ["admin","gerente"])) {
    setMensagem("erro", "Não tens permissão para editar produtos.");
    header("Location: /majo_system/pages/produtos/listar.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /majo_system/pages/produtos/listar.php"); exit(); }

// Buscar produto atual
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
if (!$produto) { setMensagem('erro', 'Produto não encontrado.'); header("Location: /majo_system/pages/produtos/listar.php"); exit(); }

$erros = [];

// --- PROCESSAR FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'codigo'           => trim($_POST['codigo'] ?? ''),
        'nome'             => trim($_POST['nome'] ?? ''),
        'descricao'        => trim($_POST['descricao'] ?? ''),
        'categoria_id'     => (int)($_POST['categoria_id'] ?? 0) ?: null,
        'preco_compra'     => (float)($_POST['preco_compra'] ?? 0),
        'preco_venda'      => (float)($_POST['preco_venda'] ?? 0),
        'quantidade_stock' => (int)($_POST['quantidade_stock'] ?? 0),
        'stock_minimo'     => (int)($_POST['stock_minimo'] ?? 5),
        'unidade_medida'   => trim($_POST['unidade_medida'] ?? 'un'),
        'status'           => $_POST['status'] ?? 'ativo',
    ];
    $dados['margem_lucro'] = calcularMargem($dados['preco_compra'], $dados['preco_venda']);

    if (empty($dados['nome']))   $erros[] = "O nome é obrigatório.";
    if (empty($dados['codigo'])) $erros[] = "O código é obrigatório.";

    // Verificar código duplicado (excepto o próprio produto)
    $check = $conn->prepare("SELECT id FROM produtos WHERE codigo = ? AND id != ?");
    $check->bind_param("si", $dados['codigo'], $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $erros[] = "Este código já está em uso por outro produto.";

    // Upload de nova imagem (se enviou)
    $nome_imagem = $produto['imagem']; // manter a imagem atual
    if (!empty($_FILES['imagem']['name'])) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $extensoes_permitidas)) {
            $erros[] = "Formato de imagem não permitido.";
        } else {
            // Apagar imagem antiga
            if ($produto['imagem'] && file_exists('../../uploads/produtos/' . $produto['imagem'])) {
                unlink('../../uploads/produtos/' . $produto['imagem']);
            }
            $nome_imagem = uniqid('prod_') . '.' . $extensao;
            move_uploaded_file($_FILES['imagem']['tmp_name'], '../../uploads/produtos/' . $nome_imagem);
        }
    }

    if (empty($erros)) {
        $stmt = $conn->prepare("
            UPDATE produtos SET
                codigo=?, nome=?, descricao=?, categoria_id=?,
                preco_compra=?, preco_venda=?, margem_lucro=?,
                quantidade_stock=?, stock_minimo=?, unidade_medida=?,
                imagem=?, status=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "sssidddiiissi",
            $dados['codigo'], $dados['nome'], $dados['descricao'], $dados['categoria_id'],
            $dados['preco_compra'], $dados['preco_venda'], $dados['margem_lucro'],
            $dados['quantidade_stock'], $dados['stock_minimo'], $dados['unidade_medida'],
            $nome_imagem, $dados['status'], $id
        );
        if ($stmt->execute()) {
            registarHistorico($conn, 'EDITAR', 'produtos', $id, "Produto '{$dados['nome']}' editado");
            setMensagem('sucesso', "Produto '{$dados['nome']}' atualizado com sucesso!");
            header("Location: /majo_system/pages/produtos/listar.php");
            exit();
        } else {
            $erros[] = "Erro ao atualizar: " . $conn->error;
        }
    }
    // Se houver erros, preenche o formulário com os dados submetidos
    $produto = array_merge($produto, $dados);
}

$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome");
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-edit"></i> Editar Produto</div>
        <div class="page-subtitulo"><?= htmlspecialchars($produto['nome']) ?></div>
    </div>
    <a href="/majo_system/pages/produtos/listar.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<?php if (!empty($erros)): ?>
<div class="alerta alerta-erro">
    <i class="fas fa-exclamation-circle"></i>
    <ul style="margin:0; padding-left:20px;">
        <?php foreach($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;"><i class="fas fa-info-circle"></i> Informações Básicas</h3>
                <div class="form-grid">
                    <div class="form-grupo">
                        <label>Código *</label>
                        <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($produto['codigo']) ?>" required>
                    </div>
                    <div class="form-grupo">
                        <label>Categoria</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">Sem categoria</option>
                            <?php while($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= $produto['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-grupo full">
                        <label>Nome *</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($produto['nome']) ?>" required>
                    </div>
                    <div class="form-grupo full">
                        <label>Descrição</label>
                        <textarea name="descricao" class="form-control"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;"><i class="fas fa-tag"></i> Preços (Kz)</h3>
                <div class="form-grid">
                    <div class="form-grupo">
                        <label>Preço de Compra</label>
                        <input type="number" id="preco_compra" name="preco_compra" class="form-control" value="<?= $produto['preco_compra'] ?>" step="0.01" min="0">
                    </div>
                    <div class="form-grupo">
                        <label>Preço de Venda *</label>
                        <input type="number" id="preco_venda" name="preco_venda" class="form-control" value="<?= $produto['preco_venda'] ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-grupo">
                        <label>Margem de Lucro (%)</label>
                        <input type="number" id="margem_lucro" name="margem_lucro" class="form-control" value="<?= $produto['margem_lucro'] ?>" readonly style="background:var(--fundo);">
                    </div>
                    <div class="form-grupo">
                        <label>Unidade</label>
                        <select name="unidade_medida" class="form-control">
                            <?php foreach(['un','kg','g','lt','ml','cx','pç','resma','mt'] as $u): ?>
                            <option value="<?= $u ?>" <?= $produto['unidade_medida'] == $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;"><i class="fas fa-warehouse"></i> Stock</h3>
                <div class="form-grid">
                    <div class="form-grupo">
                        <label>Quantidade em Stock</label>
                        <input type="number" name="quantidade_stock" class="form-control" value="<?= $produto['quantidade_stock'] ?>" min="0">
                    </div>
                    <div class="form-grupo">
                        <label>Stock Mínimo</label>
                        <input type="number" name="stock_minimo" class="form-control" value="<?= $produto['stock_minimo'] ?>" min="0">
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card" style="text-align:center;">
                <h3 style="margin-bottom:16px; color:var(--primaria); font-size:15px;"><i class="fas fa-image"></i> Imagem</h3>
                <?php if ($produto['imagem']): ?>
                <img src="/majo_system/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" id="preview-imagem"
                     style="max-width:100%; border-radius:10px; max-height:160px; object-fit:cover; margin-bottom:10px;">
                <?php else: ?>
                <img id="preview-imagem" src="" alt="" style="display:none; max-width:100%; border-radius:10px; margin-bottom:10px;">
                <?php endif; ?>
                <div class="upload-area" onclick="document.getElementById('input-imagem').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p style="font-size:13px;">Clica para trocar a imagem</p>
                </div>
                <input type="file" id="input-imagem" name="imagem" accept="image/*" style="display:none" onchange="previewImagem(this)">
            </div>
            <div class="card">
                <h3 style="margin-bottom:16px; color:var(--primaria); font-size:15px;"><i class="fas fa-toggle-on"></i> Status</h3>
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:normal; margin-bottom:10px;">
                    <input type="radio" name="status" value="ativo" <?= $produto['status'] == 'ativo' ? 'checked' : '' ?>>
                    <span class="badge badge-ativo">Ativo</span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:normal;">
                    <input type="radio" name="status" value="inativo" <?= $produto['status'] == 'inativo' ? 'checked' : '' ?>>
                    <span class="badge badge-inativo">Inativo</span>
                </label>
            </div>
            <button type="submit" class="btn btn-acento" style="width:100%; justify-content:center; padding:14px;">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
            <a href="/majo_system/pages/produtos/listar.php" class="btn btn-outline" style="width:100%; justify-content:center;">Cancelar</a>
        </div>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>
