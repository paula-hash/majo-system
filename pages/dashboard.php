<?php
// ============================================================
// FICHEIRO: pages/dashboard.php
// FUNÇÃO: Página inicial com estatísticas e gráficos
// ============================================================
require_once '../includes/sessao.php';
require_once '../includes/conexao.php';
verificarLogin(); // Garante que só entra quem está logado

// --- BUSCAR ESTATÍSTICAS DO BANCO DE DADOS ---

// Total de produtos
$total_produtos = $conn->query("SELECT COUNT(*) as total FROM produtos")->fetch_assoc()['total'];

// Produtos ativos
$total_ativos = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE status='ativo'")->fetch_assoc()['total'];

// Produtos com stock baixo (abaixo do mínimo)
$stock_baixo = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE quantidade_stock <= stock_minimo")->fetch_assoc()['total'];

// Total de categorias
$total_categorias = $conn->query("SELECT COUNT(*) as total FROM categorias")->fetch_assoc()['total'];

// Últimos 5 produtos cadastrados
$ultimos = $conn->query("
    SELECT p.nome, p.preco_venda, p.quantidade_stock, p.status, c.nome as categoria, p.criado_em
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.criado_em DESC LIMIT 5
");

// Dados para o gráfico de categorias
$dados_grafico = $conn->query("
    SELECT c.nome, COUNT(p.id) as quantidade
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.id
    GROUP BY c.id, c.nome
");
$labels_grafico = []; $valores_grafico = [];
while ($row = $dados_grafico->fetch_assoc()) {
    $labels_grafico[] = $row['nome'];
    $valores_grafico[] = $row['quantidade'];
}

// Dados para gráfico de stock
$dados_stock = $conn->query("SELECT nome, quantidade_stock FROM produtos ORDER BY quantidade_stock DESC LIMIT 6");
$labels_stock = []; $valores_stock = [];
while ($row = $dados_stock->fetch_assoc()) {
    $labels_stock[] = $row['nome'];
    $valores_stock[] = $row['quantidade_stock'];
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo">Dashboard</div>
        <div class="page-subtitulo">Bem-vindo, <?= htmlspecialchars($_SESSION['nome']) ?>! Aqui está o resumo do sistema.</div>
    </div>
</div>

<!-- CARTÕES DE ESTATÍSTICA -->
<div class="cards-grid">
    <div class="card-stat">
        <div class="card-stat-icon icon-azul"><i class="fas fa-box"></i></div>
        <div>
            <div class="card-stat-valor"><?= $total_produtos ?></div>
            <div class="card-stat-label">Total de Produtos</div>
        </div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-verde"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="card-stat-valor"><?= $total_ativos ?></div>
            <div class="card-stat-label">Produtos Ativos</div>
        </div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-vermelho"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="card-stat-valor"><?= $stock_baixo ?></div>
            <div class="card-stat-label">Stock Baixo</div>
        </div>
    </div>
    <div class="card-stat">
        <div class="card-stat-icon icon-laranja"><i class="fas fa-tags"></i></div>
        <div>
            <div class="card-stat-valor"><?= $total_categorias ?></div>
            <div class="card-stat-label">Categorias</div>
        </div>
    </div>
</div>

<!-- GRÁFICOS -->
<div class="graficos-grid">
    <div class="card">
        <h3 style="margin-bottom:16px; font-size:15px; color:var(--primaria)">
            <i class="fas fa-chart-pie"></i> Produtos por Categoria
        </h3>
        <div class="grafico-container">
            <canvas id="grafico-categorias"></canvas>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-bottom:16px; font-size:15px; color:var(--primaria)">
            <i class="fas fa-chart-bar"></i> Stock por Produto
        </h3>
        <div class="grafico-container">
            <canvas id="grafico-stock"></canvas>
        </div>
    </div>
</div>

<!-- ÚLTIMOS PRODUTOS -->
<div class="card" style="margin-top:24px">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:15px; color:var(--primaria)"><i class="fas fa-clock"></i> Últimos Produtos Cadastrados</h3>
        <a href="produtos/listar.php" class="btn btn-outline btn-sm">Ver Todos</a>
    </div>
    <div class="tabela-container">
        <table>
            <thead>
                <tr>
                    <th>Produto</th><th>Categoria</th>
                    <th>Preço Venda</th><th>Stock</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $ultimos->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? 'Sem categoria') ?></td>
                    <td><?= formatarPreco($p['preco_venda']) ?></td>
                    <td><?= $p['quantidade_stock'] ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SCRIPT DOS GRÁFICOS -->
<script>
// Gráfico de rosca - Categorias
new Chart(document.getElementById('grafico-categorias'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_grafico) ?>,
        datasets: [{
            data: <?= json_encode($valores_grafico) ?>,
            backgroundColor: ['#1a3c5e','#2563a8','#f59e0b','#10b981','#ef4444','#8b5cf6'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

// Gráfico de barras - Stock
new Chart(document.getElementById('grafico-stock'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_stock) ?>,
        datasets: [{
            label: 'Quantidade em Stock',
            data: <?= json_encode($valores_stock) ?>,
            backgroundColor: '#2563a8', borderRadius: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
