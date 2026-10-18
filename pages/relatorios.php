<?php
require_once '../includes/sessao.php';
require_once '../includes/conexao.php';
verificarGerenteOuAdmin();

$tipo      = $_GET['tipo']      ?? 'todos';
$categoria = (int)($_GET['categoria'] ?? 0);

// ── Query com prepared statement para evitar SQL injection ──
$where_parts = ["1=1"];
$params      = [];
$tipos_param = "";

if ($tipo === 'ativos')     { $where_parts[] = "p.status = 'ativo'"; }
if ($tipo === 'inativos')   { $where_parts[] = "p.status = 'inativo'"; }
if ($tipo === 'stock_baixo'){ $where_parts[] = "p.quantidade_stock <= p.stock_minimo"; }
if ($categoria > 0) {
    $where_parts[] = "p.categoria_id = ?";
    $params[]      = $categoria;
    $tipos_param  .= "i";
}

$where = "WHERE " . implode(" AND ", $where_parts);

// Buscar produtos
$sql_prod = "SELECT p.*, c.nome as categoria_nome
             FROM produtos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             $where ORDER BY p.nome";

$stmt_prod = $conn->prepare($sql_prod);
if (!empty($params)) $stmt_prod->bind_param($tipos_param, ...$params);
$stmt_prod->execute();
$produtos = $stmt_prod->get_result();

// Estatísticas — query separada e correcta
$sql_stats = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN p.status='ativo' THEN 1 ELSE 0 END) as ativos,
    SUM(CASE WHEN p.quantidade_stock <= p.stock_minimo THEN 1 ELSE 0 END) as stock_baixo,
    COALESCE(SUM(p.preco_venda * p.quantidade_stock), 0) as valor_total_stock
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    $where";

$stmt_stats = $conn->prepare($sql_stats);
if (!empty($params)) $stmt_stats->bind_param($tipos_param, ...$params);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome");

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-file-alt"></i> Relatórios</div>
        <div class="page-subtitulo">Visualiza e exporta relatórios dos produtos</div>
    </div>
    <div style="display:flex; gap:10px;">
        <button onclick="exportarExcel('tabela-relatorio','Relatorio_MAJO')" class="btn btn-exportar">
            <i class="fas fa-file-excel"></i> Excel
        </button>
        <button onclick="exportarPDF('Relatorio de Produtos MAJO LDA')" class="btn btn-exportar">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
    </div>
</div>

<!-- FILTROS -->
<div class="card" style="margin-bottom:20px; padding:16px;">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div>
            <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Tipo</label>
            <select name="tipo" class="form-control">
                <option value="todos"       <?= $tipo==='todos'       ? 'selected':'' ?>>Todos os Produtos</option>
                <option value="ativos"      <?= $tipo==='ativos'      ? 'selected':'' ?>>Apenas Ativos</option>
                <option value="inativos"    <?= $tipo==='inativos'    ? 'selected':'' ?>>Apenas Inativos</option>
                <option value="stock_baixo" <?= $tipo==='stock_baixo' ? 'selected':'' ?>>Stock Baixo</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Categoria</label>
            <select name="categoria" class="form-control">
                <option value="0">Todas as Categorias</option>
                <?php
                $categorias->data_seek(0);
                while($cat = $categorias->fetch_assoc()):
                ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria==$cat['id'] ? 'selected':'' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primario"><i class="fas fa-filter"></i> Filtrar</button>
        <a href="relatorios.php" class="btn btn-outline">Limpar</a>
    </form>
</div>

<!-- CARDS ESTATÍSTICAS -->
<div class="cards-grid" style="margin-bottom:20px;">
    <div class="card-stat">
        <div class="card-stat-icon icon-azul"><i class="fas fa-box"></i></div>
        <div><div class="card-stat-valor"><?= $stats['total'] ?? 0 ?></div><div class="card-stat-label">Total de Produtos</div></div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-verde"><i class="fas fa-check-circle"></i></div>
        <div><div class="card-stat-valor"><?= $stats['ativos'] ?? 0 ?></div><div class="card-stat-label">Produtos Ativos</div></div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-vermelho"><i class="fas fa-exclamation-triangle"></i></div>
        <div><div class="card-stat-valor"><?= $stats['stock_baixo'] ?? 0 ?></div><div class="card-stat-label">Stock Baixo</div></div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-laranja"><i class="fas fa-coins"></i></div>
        <div><div class="card-stat-valor" style="font-size:15px;"><?= formatarPreco($stats['valor_total_stock'] ?? 0) ?></div><div class="card-stat-label">Valor em Stock</div></div>
    </div>
</div>

<!-- TABELA -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
        <h3 style="font-size:15px; color:var(--primaria);">
            <i class="fas fa-list"></i> Lista de Produtos
            <span style="font-size:12px; color:var(--texto-claro); font-weight:normal; margin-left:6px;">(<?= $produtos->num_rows ?> resultado(s))</span>
        </h3>
        <div style="display:flex; gap:10px; align-items:center;">
            <input type="text" id="pesquisa-rapida" class="input-pesquisa" style="width:220px;" placeholder="Pesquisa rápida...">
            <small style="color:var(--texto-claro);"><?= date('d/m/Y H:i') ?></small>
        </div>
    </div>
    <div class="tabela-container">
        <table id="tabela-relatorio">
            <thead>
                <tr>
                    <th>Código</th><th>Nome</th><th>Categoria</th>
                    <th>Preço Compra</th><th>Preço Venda</th><th>Margem</th>
                    <th>Stock</th><th>Valor em Stock</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_valor = 0;
                while($p = $produtos->fetch_assoc()):
                    $valor_stock = $p['preco_venda'] * $p['quantidade_stock'];
                    $total_valor += $valor_stock;
                ?>
                <tr>
                    <td><code style="background:var(--fundo);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($p['codigo']) ?></code></td>
                    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                    <td><?= htmlspecialchars($p['categoria_nome'] ?? '—') ?></td>
                    <td><?= formatarPreco($p['preco_compra']) ?></td>
                    <td><?= formatarPreco($p['preco_venda']) ?></td>
                    <td><?= $p['margem_lucro'] ?>%</td>
                    <td style="<?= $p['quantidade_stock'] <= $p['stock_minimo'] ? 'color:var(--perigo);font-weight:bold;' : '' ?>">
                        <?= $p['quantidade_stock'] ?>
                        <?php if($p['quantidade_stock'] <= $p['stock_minimo']): ?><i class="fas fa-exclamation-triangle" title="Stock baixo"></i><?php endif; ?>
                    </td>
                    <td><?= formatarPreco($valor_stock) ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--fundo);font-weight:700;">
                    <td colspan="7" style="text-align:right;padding:12px 16px;">Total em Stock:</td>
                    <td style="padding:12px 16px;"><?= formatarPreco($total_valor) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
