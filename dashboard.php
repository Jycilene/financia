<?php
// 1. Inicia a sessão e protege a rota
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Conecta ao banco de dados
require_once __DIR__ . '/config/database.php';

$usuario_id = $_SESSION['usuario_id'];

// 3. Calcula Iniciais do Usuário para o Avatar
$partes_nome = explode(' ', trim($_SESSION['usuario_nome']));
$iniciais = strtoupper(substr($partes_nome[0], 0, 1) . (isset($partes_nome[1]) ? substr($partes_nome[1], 0, 1) : ''));

// 4. Busca as categorias para preencher o filtro
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 5. Calcula os Totais Gerais (permanecem globais para os cards)
$sql_rec = "SELECT COALESCE(SUM(valor), 0) AS total_receitas 
            FROM movimentacoes 
            WHERE usuario_id = :usuario_id AND tipo = 'receita'";
$stmt_rec = $pdo->prepare($sql_rec);
$stmt_rec->execute([':usuario_id' => $usuario_id]);
$total_receitas = (float) $stmt_rec->fetch()['total_receitas'];

$sql_desp = "SELECT COALESCE(SUM(valor), 0) AS total_despesas 
             FROM movimentacoes 
             WHERE usuario_id = :usuario_id AND tipo = 'despesa'";
$stmt_desp = $pdo->prepare($sql_desp);
$stmt_desp->execute([':usuario_id' => $usuario_id]);
$total_despesas = (float) $stmt_desp->fetch()['total_despesas'];

$saldo_atual = $total_receitas - $total_despesas;

// 7. Despesas agrupadas por categoria para a Rosca e Legenda
$sql_cat_chart = "SELECT c.nome, COALESCE(SUM(m.valor), 0) AS total_cat 
                  FROM movimentacoes m 
                  JOIN categorias c ON m.categoria_id = c.id 
                  WHERE m.usuario_id = :usuario_id AND m.tipo = 'despesa' 
                  GROUP BY c.nome 
                  ORDER BY total_cat DESC";
$stmt_cat_chart = $pdo->prepare($sql_cat_chart);
$stmt_cat_chart->execute([':usuario_id' => $usuario_id]);
$categorias_despesas = $stmt_cat_chart->fetchAll(PDO::FETCH_ASSOC);

$labels_chart = [];
$valores_chart = [];
foreach ($categorias_despesas as $cd) {
    $labels_chart[] = $cd['nome'];
    $valores_chart[] = (float) $cd['total_cat'];
}

// --- INDICADORES DE CONTAS FIXAS PARA O MÊS ATUAL ---
$mesAtual = (int) date('m');
$anoAtual = (int) date('Y');
$hoje = date('Y-m-d');

// Garante que as ocorrências do mês já estão geradas também ao abrir o dashboard (apenas para compromissos recorrentes)
$stmt_ativas = $pdo->prepare("SELECT * FROM despesas_fixas WHERE usuario_id = :usuario_id AND ativo = TRUE AND (tipo_compromisso = 'recorrente' OR tipo_compromisso IS NULL)");
$stmt_ativas->execute([':usuario_id' => $usuario_id]);
foreach ($stmt_ativas->fetchAll(PDO::FETCH_ASSOC) as $despesa) {
    $dia = (int) $despesa['dia_vencimento'];
    $ultimoDiaMes = (int) date('t', strtotime("{$anoAtual}-{$mesAtual}-01"));
    $diaReal = min($dia, $ultimoDiaMes);
    $dataVencimento = sprintf('%04d-%02d-%02d', $anoAtual, $mesAtual, $diaReal);

    $check = $pdo->prepare("SELECT id FROM pagamentos_fixas WHERE despesa_fixa_id = :did AND mes_referencia = :mes AND ano_referencia = :ano");
    $check->execute([':did' => $despesa['id'], ':mes' => $mesAtual, ':ano' => $anoAtual]);

    if (!$check->fetch()) {
        $insert = $pdo->prepare("INSERT INTO pagamentos_fixas (despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, status) VALUES (:did, :uid, :mes, :ano, :val, :venc, 'pendente')");
        $insert->execute([':did' => $despesa['id'], ':uid' => $usuario_id, ':mes' => $mesAtual, ':ano' => $anoAtual, ':val' => $despesa['valor'], ':venc' => $dataVencimento]);
    }
}

// Busca os pagamentos fixos do mês para calcular os totais do dashboard
$stmt_fixas_dash = $pdo->prepare("SELECT * FROM pagamentos_fixas WHERE usuario_id = :usuario_id AND mes_referencia = :mes AND ano_referencia = :ano");
$stmt_fixas_dash->execute([':usuario_id' => $usuario_id, ':mes' => $mesAtual, ':ano' => $anoAtual]);
$fixasMes = $stmt_fixas_dash->fetchAll(PDO::FETCH_ASSOC);

$totalFixasPagar = 0;
$totalFixasPago = 0;
$totalFixasAtrasado = 0;

foreach ($fixasMes as $fx) {
    $val = (float) $fx['valor'];
    $totalFixasPagar += $val;
    if ($fx['status'] === 'paga') {
        $totalFixasPago += $val;
    } elseif ($fx['data_vencimento'] < $hoje) {
        $totalFixasAtrasado += $val;
    }
}

// 8. Filtros dinâmicos para a tabela
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$filtro_categoria = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;

$where_condicoes = ["m.usuario_id = :usuario_id"];
$parametros = [':usuario_id' => $usuario_id];

// Se o usuário filtrou por Tipo (receita ou despesa)
if (!empty($filtro_tipo) && in_array($filtro_tipo, ['receita', 'despesa'])) {
    $where_condicoes[] = "m.tipo = :tipo";
    $parametros[':tipo'] = $filtro_tipo;
}

// Se o usuário filtrou por Categoria
if ($filtro_categoria > 0) {
    $where_condicoes[] = "m.categoria_id = :categoria_id";
    $parametros[':categoria_id'] = $filtro_categoria;
}

// Junta todas as condições com AND
$where_sql = implode(" AND ", $where_condicoes);

// Executa a busca com os filtros aplicados
$sql_mov = "SELECT m.*, c.nome AS categoria_nome 
            FROM movimentacoes m 
            JOIN categorias c ON m.categoria_id = c.id 
            WHERE {$where_sql} 
            ORDER BY m.data_movimentacao DESC, m.id DESC";

$stmt_mov = $pdo->prepare($sql_mov);
$stmt_mov->execute($parametros);
$movimentacoes = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Painel Principal</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="container">

        <!-- CABEÇALHO -->
        <header class="header-painel">
            <div class="brand">
                <div class="brand-icon">💰</div>
                <h1>Finan<span>Cia</span></h1>
            </div>

            <div class="header-usuario">
                <div class="avatar-usuario"><?php echo $iniciais; ?></div>
                <div>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
                <a href="logout.php" class="btn-sair">Sair</a>
            </div>
        </header>

        <!-- 3 CARDS FINANCEIROS -->
        <section class="cards-grid">
            <div class="card">
                <div>
                    <div class="card-titulo">Total Receitas</div>
                    <div class="card-valor valor-receita">
                        R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="card-icone icone-receita">↗</div>
            </div>

            <div class="card">
                <div>
                    <h1 class="page-title">Olá, <?php echo htmlspecialchars($primeiro_nome); ?></h1>
                    <?php
                    $mesesEmPortugues = [
                        'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março', 'April' => 'Abril',
                        'May' => 'Maio', 'June' => 'Junho', 'July' => 'Julho', 'August' => 'Agosto',
                        'September' => 'Setembro', 'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
                    ];
                    $mesAtualNome = $mesesEmPortugues[date('F')] ?? date('F');
                    ?>
                    <p class="page-subtitle">Aqui está o resumo das suas finanças de <?php echo $mesAtualNome; ?>.</p>
                </div>

                <div class="header-right-tools">
                    <form action="dashboard.php" method="GET" class="search-input-box" style="position: relative;">
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex; align-items: center;">
                            <i data-lucide="search" class="lucide-sm"></i>
                        </span>
                        <input type="text" name="busca" placeholder="Buscar movimentações..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                    </form>

                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i> Painel Admin
                        </a>
                    <?php endif; ?>

                    <?php
                    $stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = FALSE");
                    $stmt_badge->execute([':uid' => $usuario_id]);
                    $nao_lidas = $stmt_badge->fetchColumn();
                    ?>
                    
                    <a href="notificacoes.php" style="text-decoration: none; position: relative; margin-right: 8px; display: inline-flex; align-items: center; color: var(--navy-main);" title="Notificações">
                        <i data-lucide="bell" class="lucide-md"></i>
                        <?php if ($nao_lidas > 0): ?>
                            <span style="position: absolute; top: -5px; right: -8px; background: #dc2626; color: white; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 10px;">
                                <?php echo $nao_lidas; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="perfil.php" title="Meu perfil" style="text-decoration: none;">
                            <?php if (!empty($foto_usuario)): ?>
                                <img src="<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
                            <?php else: ?>
                                <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 12px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%;"><?php echo $iniciais; ?></div>
                            <?php endif; ?>
                        </a>
                        <a href="perfil.php" style="text-decoration: none; color: inherit;">
                            <span style="font-size: 13px; font-weight: 700;"><?php echo htmlspecialchars($usuario_nome); ?></span>
                        </a>
                        <span class="badge-admin-tag"><?php echo strtoupper($usuario_perfil); ?></span>
                        <a href="logout.php" style="font-size: 12px; color: var(--text-muted); margin-left: 4px;">Sair</a>
                    </div>
                </div>
                <div class="card-icone icone-despesa">↘</div>
            </div>

            <div class="card">
                <div>
                    <div class="card-titulo">Saldo Atual</div>
                    <div class="card-valor <?php echo $saldo_atual >= 0 ? 'valor-saldo' : 'valor-despesa'; ?>">
                        R$ <?php echo number_format($saldo_atual, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="card-icone icone-saldo">💼</div>
            </div>
        </section>

        <!-- PAINEL COM O GRÁFICO -->
        <?php if ($total_receitas > 0 || $total_despesas > 0): ?>
        <section class="painel-grafico">
            <div class="grafico-info">
                <h2>Balanço Geral de Finanças</h2>
                <p>Veja a proporção entre os seus ganhos e gastos acumulados até o momento.</p>
            </div>
            <div class="grafico-canvas-wrapper">
                <canvas id="graficoFinanceiro"></canvas>
            </div>
        </section>
        <?php endif; ?>

        <!-- 🔍 BARRA DE FILTROS DINÂMICOS -->
        <form action="index.php" method="GET" class="barra-filtros">
            <div class="filtro-item">
                <label for="filtro-tipo">Tipo:</label>
                <select name="tipo" id="filtro-tipo" class="form-control">
                    <option value="">Todos os tipos</option>
                    <option value="receita" <?php echo $filtro_tipo === 'receita' ? 'selected' : ''; ?>>🟢 Apenas Receitas</option>
                    <option value="despesa" <?php echo $filtro_tipo === 'despesa' ? 'selected' : ''; ?>>🔴 Apenas Despesas</option>
                </select>
            </div>

            <div class="filtro-item">
                <label for="filtro-categoria">Categoria:</label>
                <select name="categoria_id" id="filtro-categoria" class="form-control">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria === (int)$cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['nome']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-filtrar">🔍 Filtrar</button>

            <!-- Se houver algum filtro ativo, mostra o botão para limpar -->
            <?php if (!empty($filtro_tipo) || $filtro_categoria > 0): ?>
                <a href="index.php" class="btn-limpar">✕ Limpar Filtros</a>
            <?php endif; ?>
        </form>

        <!-- SEÇÃO DA TABELA -->
        <section class="secao-tabela">
            <div class="secao-header">
                <h2>
                    Movimentações Recentes
                    <?php if (!empty($filtro_tipo) || $filtro_categoria > 0): ?>
                        <span style="font-size: 13px; font-weight: 500; color: var(--texto-secundario);">(Filtrado)</span>
                    <?php endif; ?>
                </h2>
                <a href="nova_movimentacao.php" class="btn-novo">
                    ➕ Nova Movimentação
                </a>
            </div>

            <?php if (empty($movimentacoes)): ?>
                <p style="padding: 40px; text-align: center; color: var(--texto-secundario);">
                    Nenhuma movimentação encontrada com os filtros selecionados.
                </p>
            <?php else: ?>
                <div class="tabela-wrapper">
                    <table class="tabela-financia">
                        <thead>
                            <tr>
                                <th class="col-data">Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th class="col-tipo">Tipo</th>
                                <th class="col-valor">Valor</th>
                                <th class="col-acoes">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td class="col-data"><?php echo date('d/m/Y', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($mov['descricao']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mov['categoria_nome']); ?></td>
                                    <td class="col-tipo">
                                        <span class="badge <?php echo $mov['tipo'] === 'receita' ? 'badge-receita' : 'badge-despesa'; ?>">
                                            <?php echo $mov['tipo'] === 'receita' ? '● Receita' : '● Despesa'; ?>
                                        </span>
                                    </td>
                                    <td class="col-valor" style="color: <?php echo $mov['tipo'] === 'receita' ? 'var(--receita)' : 'var(--despesa)'; ?>;">
                                        <?php echo $mov['tipo'] === 'receita' ? '+' : '-'; ?> 
                                        R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="col-acoes">
                                        <a href="editar_movimentacao.php?id=<?php echo $mov['id']; ?>" class="btn-acao btn-editar">
                                            ✏️ Editar
                                        </a>
                                        <a href="excluir_movimentacao.php?id=<?php echo $mov['id']; ?>" 
                                           onclick="return confirm('Tem certeza que deseja excluir esta movimentação?');" 
                                           class="btn-acao btn-excluir">
                                            🗑️ Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <!-- SCRIPT DO GRÁFICO (Chart.js) -->
    <?php if ($total_receitas > 0 || $total_despesas > 0): ?>
    <script>
        const ctx = document.getElementById('graficoFinanceiro').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Receitas', 'Despesas'],
                datasets: [{
                    data: [<?php echo $total_receitas; ?>, <?php echo $total_despesas; ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { family: 'Inter', size: 12, weight: 600 },
                            padding: 14
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>
