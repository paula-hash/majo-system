<?php
// ============================================================
// FICHEIRO: pages/produtos/criar.php
// FUNÇÃO: Formulário para cadastrar novo produto
// ============================================================
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarLogin();

$erros = [];
$dados = [];

// --- PROCESSAR O FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolher e limpar os dados
    $dados = [
        'codigo'          => trim($_POST['codigo'] ?? ''),
        'nome'            => trim($_POST['nome'] ?? ''),
        'descricao'       => trim($_POST['descricao'] ?? ''),
        'categoria_id'    => (int)($_POST['categoria_id'] ?? 0) ?: null,
        'preco_compra'    => (float)($_POST['preco_compra'] ?? 0),
        'preco_venda'     => (float)($_POST['preco_venda'] ?? 0),
        'quantidade_stock'=> (int)($_POST['quantidade_stock'] ?? 0),
        'stock_minimo'    => (int)($_POST['stock_minimo'] ?? 5),
        'unidade_medida'  => trim($_POST['unidade_medida'] ?? 'un'),
        'status'          => $_POST['status'] ?? 'ativo',
        'criado_por'      => $_SESSION['utilizador_id']
    ];

    // Calcular margem
    $dados['margem_lucro'] = calcularMargem($dados['preco_compra'], $dados['preco_venda']);

    // Validações
    if (empty($dados['codigo'])) $erros[] = "O código é obrigatório.";
    if (empty($dados['nome']))   $erros[] = "O nome é obrigatório.";
    if ($dados['preco_venda'] <= 0) $erros[] = "O preço de venda deve ser maior que zero.";

    // Verificar se o código já existe
    $check = $conn->prepare("SELECT id FROM produtos WHERE codigo = ?");
    $check->bind_param("s", $dados['codigo']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $erros[] = "Este código já está em uso.";

    // --- UPLOAD DA IMAGEM ---
    $nome_imagem = null;
    if (!empty($_FILES['imagem']['name'])) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $extensoes_permitidas)) {
            $erros[] = "Formato de imagem não permitido. Use: JPG, PNG, GIF ou WEBP.";
        } elseif ($_FILES['imagem']['size'] > 2 * 1024 * 1024) { // 2MB
            $erros[] = "A imagem não pode ter mais de 2MB.";
        } else {
            $nome_imagem = uniqid('prod_') . '.' . $extensao;
            $destino = '../../uploads/produtos/' . $nome_imagem;
            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $erros[] = "Erro ao fazer upload da imagem.";
                $nome_imagem = null;
            }
        }
    }

    // --- SALVAR NO BANCO DE DADOS ---
    if (empty($erros)) {
        $stmt = $conn->prepare("
            INSERT INTO produtos
            (codigo, nome, descricao, categoria_id, preco_compra, preco_venda, margem_lucro,
             quantidade_stock, stock_minimo, unidade_medida, imagem, status, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssidddiisssi",
            $dados['codigo'], $dados['nome'], $dados['descricao'], $dados['categoria_id'],
            $dados['preco_compra'], $dados['preco_venda'], $dados['margem_lucro'],
            $dados['quantidade_stock'], $dados['stock_minimo'], $dados['unidade_medida'],
            $nome_imagem, $dados['status'], $dados['criado_por']
        );

        if ($stmt->execute()) {
            $novo_id = $conn->insert_id;
            registarHistorico($conn, 'CRIAR', 'produtos', $novo_id, "Produto '{$dados['nome']}' criado");
            setMensagem('sucesso', "Produto '{$dados['nome']}' cadastrado com sucesso!");
            header("Location: /majo_system/pages/produtos/listar.php");
            exit();
        } else {
            $erros[] = "Erro ao salvar no banco de dados: " . $conn->error;
        }
    }
}

// Gerar código automático
$codigo_sugerido = gerarCodigo($conn);

// Buscar categorias
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome");

require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-plus-circle"></i> Novo Produto</div>
        <div class="page-subtitulo">Preenche os dados para cadastrar um produto</div>
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

        <!-- COLUNA PRINCIPAL -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Informações Básicas -->
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;">
                    <i class="fas fa-info-circle"></i> Informações Básicas
                </h3>
                <div class="form-grid">
                    <div class="form-grupo">
                        <label>Código do Produto *</label>
                        <input type="text" name="codigo" class="form-control"
                               value="<?= htmlspecialchars($dados['codigo'] ?? $codigo_sugerido) ?>"
                               placeholder="Ex: PRD001" required>
                    </div>
                    <div class="form-grupo">
                        <label>Categoria</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">Sem categoria</option>
                            <?php while($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($dados['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-grupo full">
                        <label>Nome do Produto *</label>
                        <input type="text" name="nome" class="form-control"
                               value="<?= htmlspecialchars($dados['nome'] ?? '') ?>"
                               placeholder="Nome completo do produto" required>
                    </div>
                    <div class="form-grupo full">
                        <label>Descrição</label>
                        <textarea name="descricao" class="form-control"
                                  placeholder="Descreve o produto..."><?= htmlspecialchars($dados['descricao'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Preços -->
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;">
                    <i class="fas fa-tag"></i> Preços (em Kwanzas)
                </h3>
                <div class="form-grid">
                    <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                    <div class="form-grupo">
                        <label>Preço de Compra (Kz)</label>
                        <input type="number" id="preco_compra" name="preco_compra" class="form-control"
                               value="<?= $dados['preco_compra'] ?? 0 ?>"
                               step="0.01" min="0" placeholder="0.00">
                    </div>
                    <?php endif; ?>
                    <div class="form-grupo">
                        <label>Preço de Venda (Kz) *</label>
                        <input type="number" id="preco_venda" name="preco_venda" class="form-control"
                               value="<?= $dados['preco_venda'] ?? 0 ?>"
                               step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                    <div class="form-grupo">
                        <label>Margem de Lucro (%)</label>
                        <input type="number" id="margem_lucro" name="margem_lucro" class="form-control"
                               value="<?= $dados['margem_lucro'] ?? 0 ?>"
                               step="0.01" readonly style="background:var(--fundo);">
                        <small style="color:var(--texto-claro);">Calculada automaticamente</small>
                    </div>
                    <?php endif; ?>
                    <div class="form-grupo">
                        <label>Unidade de Medida</label>
                        <select name="unidade_medida" class="form-control">
                            <?php foreach(['un','kg','g','lt','ml','cx','pç','resma','mt'] as $u): ?>
                            <option value="<?= $u ?>" <?= ($dados['unidade_medida'] ?? 'un') == $u ? 'selected' : '' ?>>
                                <?= $u ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stock -->
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;">
                    <i class="fas fa-warehouse"></i> Stock
                </h3>
                <div class="form-grid">
                    <div class="form-grupo">
                        <label>Quantidade em Stock</label>
                        <input type="number" name="quantidade_stock" class="form-control"
                               value="<?= $dados['quantidade_stock'] ?? 0 ?>" min="0">
                    </div>
                    <div class="form-grupo">
                        <label>Stock Mínimo (alerta)</label>
                        <input type="number" name="stock_minimo" class="form-control"
                               value="<?= $dados['stock_minimo'] ?? 5 ?>" min="0">
                        <small style="color:var(--texto-claro);">Quando atingir este valor, recebe alerta</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUNA LATERAL -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Imagem -->
            <div class="card">
                <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;">
                    <i class="fas fa-image"></i> Imagem do Produto
                </h3>
                <div class="upload-area" onclick="document.getElementById('input-imagem').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Clica para escolher</p>
                    <small>JPG, PNG, WEBP até 2MB</small>
                    <img id="preview-imagem" src="" alt="" style="display:none; max-width:100%; border-radius:10px; margin-top:10px;">
                </div>
                <input type="file" id="input-imagem" name="imagem" accept="image/*"
                       style="display:none" onchange="previewImagem(this)">
            </div>

            <!-- Status -->
            <div class="card">
                <h3 style="margin-bottom:16px; color:var(--primaria); font-size:15px;">
                    <i class="fas fa-toggle-on"></i> Status
                </h3>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="status" value="ativo" <?= ($dados['status'] ?? 'ativo') == 'ativo' ? 'checked' : '' ?>>
                        <span class="badge badge-ativo">Ativo</span>
                        <span style="font-size:12px; color:var(--texto-claro);">Produto disponível</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="status" value="inativo" <?= ($dados['status'] ?? '') == 'inativo' ? 'checked' : '' ?>>
                        <span class="badge badge-inativo">Inativo</span>
                        <span style="font-size:12px; color:var(--texto-claro);">Produto indisponível</span>
                    </label>
                </div>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-acento" style="width:100%; justify-content:center; padding:14px; font-size:15px;">
                <i class="fas fa-save"></i> Cadastrar Produto
            </button>
            <a href="/majo_system/pages/produtos/listar.php" class="btn btn-outline" style="width:100%; justify-content:center;">
                Cancelar
            </a>
        </div>
    </div>
</form>

<script>
// Mostrar preview da imagem
function previewImagem(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview-imagem');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>