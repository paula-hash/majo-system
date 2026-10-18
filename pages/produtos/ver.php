<?php
// ============================================================
// FICHEIRO: pages/produtos/ver.php
// FUNÇÃO: Ver detalhes completos de um produto + QR Code
// ============================================================
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: listar.php"); exit(); }

// Buscar produto
$stmt = $conn->prepare("
    SELECT p.*, c.nome as categoria_nome, u.nome as criado_por_nome
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN utilizadores u ON p.criado_por = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) { setMensagem('erro', 'Produto não encontrado.'); header("Location: listar.php"); exit(); }

require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-box"></i> <?= htmlspecialchars($produto['nome']) ?></div>
        <div class="page-subtitulo">Código: <?= htmlspecialchars($produto['codigo']) ?></div>
    </div>
    <div style="display:flex; gap:10px;">
        <?php if (in_array($_SESSION['perfil'], ['admin','gerente'])): ?>
        <a href="editar.php?id=<?= $id ?>" class="btn btn-primario">
            <i class="fas fa-edit"></i> Editar
        </a>
        <?php endif; ?>
        <a href="/majo_system/pages/produtos/listar.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">

    <!-- DETALHES DO PRODUTO -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card">
            <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;">
                <i class="fas fa-info-circle"></i> Informações Gerais
            </h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <?php
                $infos = [
                    'Nome' => $produto['nome'],
                    'Código' => $produto['codigo'],
                    'Categoria' => $produto['categoria_nome'] ?? 'Sem categoria',
                    'Unidade' => $produto['unidade_medida'],
                    'Status' => ucfirst($produto['status']),
                    'Cadastrado por' => $produto['criado_por_nome'] ?? 'N/A',
                    'Data de Cadastro' => date('d/m/Y H:i', strtotime($produto['criado_em'])),
                    'Última Atualização' => date('d/m/Y H:i', strtotime($produto['atualizado_em'])),
                ];
                foreach ($infos as $label => $valor):
                ?>
                <div style="border-bottom:1px solid var(--borda); padding-bottom:12px;">
                    <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;"><?= $label ?></div>
                    <div style="font-weight:600;"><?= htmlspecialchars($valor) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($produto['descricao']): ?>
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--borda);">
                <div style="font-size:11px; color:var(--texto-claro); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">Descrição</div>
                <p><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;"><i class="fas fa-tag"></i> Preços</h3>
            <div style="display:grid; grid-template-columns:<?= in_array($_SESSION['perfil'], ['admin','gerente']) ? '1fr 1fr 1fr' : '1fr' ?>; gap:16px; text-align:center;">
                <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <div style="background:var(--fundo); border-radius:12px; padding:20px;">
                    <div style="font-size:12px; color:var(--texto-claro);">Preço de Compra</div>
                    <div style="font-size:22px; font-weight:800; color:var(--primaria); margin-top:4px;"><?= formatarPreco($produto['preco_compra']) ?></div>
                </div>
                <?php endif; ?>
                <div style="background:var(--fundo); border-radius:12px; padding:20px;">
                    <div style="font-size:12px; color:var(--texto-claro);">Preço de Venda</div>
                    <div style="font-size:22px; font-weight:800; color:var(--sucesso); margin-top:4px;"><?= formatarPreco($produto['preco_venda']) ?></div>
                </div>
                <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <div style="background:var(--fundo); border-radius:12px; padding:20px;">
                    <div style="font-size:12px; color:var(--texto-claro);">Margem de Lucro</div>
                    <div style="font-size:22px; font-weight:800; color:var(--acento); margin-top:4px;"><?= $produto['margem_lucro'] ?>%</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:20px; color:var(--primaria); font-size:15px;"><i class="fas fa-warehouse"></i> Stock</h3>
            <div style="display:flex; align-items:center; gap:20px;">
                <div style="font-size:48px; font-weight:900; color:<?= $produto['quantidade_stock'] <= $produto['stock_minimo'] ? 'var(--perigo)' : 'var(--sucesso)' ?>">
                    <?= $produto['quantidade_stock'] ?>
                </div>
                <div>
                    <div style="font-size:13px; color:var(--texto-claro);">unidades em stock</div>
                    <div style="font-size:12px; margin-top:4px;">Stock mínimo: <strong><?= $produto['stock_minimo'] ?></strong></div>
                    <?php if ($produto['quantidade_stock'] <= $produto['stock_minimo']): ?>
                    <div style="color:var(--perigo); font-size:12px; margin-top:4px; font-weight:600;">
                        <i class="fas fa-exclamation-triangle"></i> Stock baixo! Necessita de reposição.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- COLUNA LATERAL: IMAGEM E QR CODE -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <!-- Imagem -->
        <div class="card" style="text-align:center;">
            <?php if ($produto['imagem']): ?>
                <img src="/majo_system/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>"
                     style="max-width:100%; border-radius:12px; max-height:220px; object-fit:cover;">
            <?php else: ?>
                <div style="height:160px; background:var(--fundo); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--texto-claro);">
                    <i class="fas fa-image" style="font-size:48px; opacity:0.3;"></i>
                </div>
                <p style="color:var(--texto-claro); font-size:12px; margin-top:8px;">Sem imagem</p>
            <?php endif; ?>
        </div>

        <!-- QR CODE -->
        <div class="card" style="text-align:center;">
            <h3 style="margin-bottom:16px; color:var(--primaria); font-size:15px;">
                <i class="fas fa-qrcode"></i> QR Code
            </h3>
            <canvas id="qrcode-canvas" style="max-width:160px; border-radius:8px;"></canvas>
            <p style="font-size:11px; color:var(--texto-claro); margin-top:8px;"><?= htmlspecialchars($produto['codigo']) ?></p>
            <button onclick="baixarQR()" class="btn btn-outline btn-sm" style="margin-top:10px; width:100%;">
                <i class="fas fa-download"></i> Baixar QR Code
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
const dadosProduto = `MAJO,LDA\nCód: <?= $produto['codigo'] ?>\nNome: <?= addslashes($produto['nome']) ?>\nPreço: <?= formatarPreco($produto['preco_venda']) ?>`;
QRCode.toCanvas(document.getElementById('qrcode-canvas'), '<?= $produto['codigo'] ?>', { width: 160 });

function baixarQR() {
    const canvas = document.getElementById('qrcode-canvas');
    const link = document.createElement('a');
    link.download = 'qrcode_<?= $produto['codigo'] ?>.png';
    link.href = canvas.toDataURL();
    link.click();
}
</script>

<?php require_once '../../includes/footer.php'; ?>