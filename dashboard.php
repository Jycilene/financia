<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gerador_notificacoes.php'; // Inclui o motor de notificações

// Executa a verificação automática de notificações ao abrir o dashboard
verificarEGeraNotificacoes($pdo, $usuario_id);

// 5. Busca as categorias cadastradas
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 6. Calcula os Totais Gerais
$sql_rec = "SELECT COALESCE(SUM(valor), 0) AS total_receitas, COUNT(id) AS qtd_receitas 
            FROM movimentacoes 
            WHERE usuario_id = :usuario_id AND tipo = 'receita'";
$stmt_rec = $pdo->prepare($sql_rec);
$stmt_rec->execute([':usuario_id' => $usuario_id]);
$dados_rec = $stmt_rec->fetch();
$total_receitas = (float) $dados_rec['total_receitas'];
$qtd_receitas = (int) $dados_rec['qtd_receitas'];

$sql_desp = "SELECT COALESCE(SUM(valor), 0) AS total_despesas, COUNT(id) AS qtd_despesas 
             FROM movimentacoes 
             WHERE usuario_id = :usuario_id AND tipo = 'despesa'";
$stmt_desp = $pdo->prepare($sql_desp);
$stmt_desp->execute([':usuario_id' => $usuario_id]);
$dados_desp = $stmt_desp->fetch();
$total_despesas = (float) $dados_desp['total_despesas'];
$qtd_despesas = (int) $dados_desp['qtd_despesas'];

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

// Garante que as ocorrências do mês já estão geradas também ao abrir o dashboard (apenas recorrentes)
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
$filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

$where_condicoes = ["m.usuario_id = :usuario_id"];
$parametros = [':usuario_id' => $usuario_id];

if (!empty($filtro_tipo) && in_array($filtro_tipo, ['receita', 'despesa'])) {
    $where_condicoes[] = "m.tipo = :tipo";
    $parametros[':tipo'] = $filtro_tipo;
}

if ($filtro_categoria > 0) {
    $where_condicoes[] = "m.categoria_id = :categoria_id";
    $parametros[':categoria_id'] = $filtro_categoria;
}

if (!empty($filtro_busca)) {
    $where_condicoes[] = "m.descricao ILIKE :busca";
    $parametros[':busca'] = "%{$filtro_busca}%";
}

$where_sql = implode(" AND ", $where_condicoes);

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
    <title>FinanCia - Visão Geral</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="app-wrapper">

        <!-- MENU LATERAL -->
        <aside class="app-sidebar">
            <div class="sidebar-top">
                <a href="dashboard.php" class="brand brand-dark sidebar-brand" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <div class="brand-icon"><i data-lucide="check"></i></div>
                    <span class="brand-text">FinanCia</span>
                </a>

                <div class="sidebar-section-title">Menu Principal</div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-item active">
                        <span class="nav-item-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span>Visão geral</span>
                    </a>
                    <a href="movimentacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="arrow-left-right"></i></span>
                        <span>Movimentações</span>
                    </a>
                    <a href="nova_movimentacao.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="circle-plus"></i></span>
                        <span>Nova movimentação</span>
                    </a>
                    <a href="contas_fixas.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="calendar-days"></i></span>
                        <span>Contas fixas</span>
                    </a>
                    <a href="notificacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="bell"></i></span>
                        <span>Notificações</span>
                    </a>
                    <a href="perfil.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="user-round"></i></span>
                        <span>Meu perfil</span>
                    </a>

                    <?php if ($eh_admin): ?>
                        <div class="sidebar-section-title" style="margin-top: 15px;">Administração</div>
                        <a href="admin.php" class="nav-item">
                            <span class="nav-item-icon"><i data-lucide="shield-check"></i></span>
                            <span>Painel Admin</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Widget Inferior da Sidebar -->
            <div class="sidebar-bottom">
                <div class="sidebar-savings-card">
                    <div class="savings-label">Saldo do mês</div>
                    <div class="savings-desc">Você economizou 18% a mais que em setembro.</div>
                </div>

                <div class="sidebar-user-card">
                    <a href="perfil.php" title="Meu perfil" style="text-decoration: none;">
                        <?php if (!empty($foto_usuario)): ?>
                            <img src="<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
                        <?php else: ?>
                            <div class="user-avatar-circle" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%; font-weight: bold; font-size: 14px;"><?php echo $iniciais; ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="user-meta-info" style="overflow: hidden;">
                        <a href="perfil.php" style="text-decoration: none; color: inherit;">
                            <div class="user-name-text" style="font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($usuario_nome); ?></div>
                        </a>
                        <a href="logout.php" class="btn-sidebar-sair" style="font-size: 11px; color: #dc2626; text-decoration: none;">Sair</a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL DO DASHBOARD -->
        <main class="app-main-content">

            <!-- Cabeçalho Superior -->
            <header class="app-header">
                <div>
                    <h1 class="page-title">Olá, <?php echo htmlspecialchars($primeiro_nome); ?></h1>
                    <?php
                    $mesesEmPortugues = [
                        'January' => 'Outubro', 'February' => 'Fevereiro', 'March' => 'Março', 'April' => 'Abril',
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
            </header>

            <!-- CARDS DE MÉTRICAS (3 KPIs) -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Saldo atual</div>
                        <div class="kpi-value">R$ <?php echo number_format($saldo_atual, 2, ',', '.'); ?></div>
                        <div class="kpi-subtext">+12,4% vs. setembro</div>
                    </div>
                    <div class="kpi-icon-pill navy"><i data-lucide="wallet"></i></div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Total de receitas</div>
                        <div class="kpi-value" style="color: var(--emerald-main);">
                            R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?>
                        </div>
                        <div class="kpi-subtext muted"><?php echo $qtd_receitas; ?> lançamentos no mês</div>
                    </div>
                    <div class="kpi-icon-pill emerald"><i data-lucide="trending-up"></i></div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Total de despesas</div>
                        <div class="kpi-value">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></div>
                        <div class="kpi-subtext muted"><?php echo $qtd_despesas; ?> lançamentos no mês</div>
                    </div>
                    <div class="kpi-icon-pill red"><i data-lucide="trending-down"></i></div>
                </div>
            </section>

            <!-- CARTÃO DE CONTAS FIXAS -->
            <div class="dashboard-card-box" style="margin-top: 24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <h3 style="font-size: 15px; font-weight: 700; color: var(--navy-main); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="calendar-days" class="lucide-md" style="color: var(--navy-main);"></i> Resumo de Contas Fixas (Mês Atual)
                    </h3>
                    <a href="contas_fixas.php" style="font-size: 12px; color: var(--emerald-main); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        Ver todas <i data-lucide="arrow-right" class="lucide-xs"></i>
                    </a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div style="background-color: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Total fixo do mês</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--navy-main); margin-top: 4px;">
                            R$ <?php echo number_format($totalFixasPagar, 2, ',', '.'); ?>
                        </div>
                    </div>

                    <div style="background-color: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; color: var(--emerald-main); font-weight: 600;">Contas pagas</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--emerald-main); margin-top: 4px;">
                            R$ <?php echo number_format($totalFixasPago, 2, ',', '.'); ?>
                        </div>
                    </div>

                    <div style="background-color: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; color: #dc2626; font-weight: 600;">Em atraso / Pendentes</div>
                        <div style="font-size: 18px; font-weight: 800; color: #dc2626; margin-top: 4px;">
                            R$ <?php echo number_format($totalFixasAtrasado, 2, ',', '.'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTEÚDO PRINCIPAL EM 2 COLUNAS -->
            <div class="dashboard-grid-content">

                <!-- COLUNA ESQUERDA -->
                <div class="dashboard-card-box">
                    <div class="box-header-flex">
                        <div>
                            <h2 class="box-title">Movimentações recentes</h2>
                        </div>
                        <a href="nova_movimentacao.php" class="btn-nova-mov-navy">
                            + Nova movimentação
                        </a>
                    </div>

                    <form action="dashboard.php" method="GET" class="filter-row-inline">
                        <select name="tipo" class="select-filter-compact">
                            <option value="">Tipo: Todos</option>
                            <option value="receita" <?php echo $filtro_tipo === 'receita' ? 'selected' : ''; ?>>Receitas</option>
                            <option value="despesa" <?php echo $filtro_tipo === 'despesa' ? 'selected' : ''; ?>>Despesas</option>
                        </select>

                        <select name="categoria_id" class="select-filter-compact">
                            <option value="">Categoria: Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['nome']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-filter-navy">Filtrar</button>
                        <?php if (!empty($filtro_tipo) || $filtro_categoria > 0 || !empty($filtro_busca)): ?>
                            <a href="dashboard.php" class="btn-clear-gray" style="text-decoration: none;">Limpar</a>
                        <?php endif; ?>
                    </form>

                    <div class="table-responsive">
                        <table class="financia-table">
                            <thead>
                                <tr>
                                    <th>Descrição / Categoria</th>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th style="text-align: right;">Valor · Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimentacoes)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            Nenhuma movimentação registrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimentacoes as $mov): ?>
                                        <tr>
                                            <td>
                                                <div class="table-desc-wrapper">
                                                    <div class="table-icon-circle">
                                                        <i data-lucide="<?php echo $mov['tipo'] === 'receita' ? 'briefcase' : 'shopping-cart'; ?>" class="lucide-sm"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($mov['descricao']); ?></strong>
                                                        <span class="badge-categoria-pill"><?php echo htmlspecialchars($mov['categoria_nome']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="color: var(--text-muted);">
                                                <?php 
                                                $mesesAbreviados = [
                                                    'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
                                                    'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
                                                    'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
                                                ];
                                                $dia = date('d', strtotime($mov['data_movimentacao']));
                                                $mesIngles = date('M', strtotime($mov['data_movimentacao']));
                                                $ano = date('Y', strtotime($mov['data_movimentacao']));
                                                echo $dia . ' ' . ($mesesAbreviados[$mesIngles] ?? $mesIngles) . ' ' . $ano;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="<?php echo $mov['tipo'] === 'receita' ? 'badge-tipo-emerald' : 'badge-tipo-red'; ?>">
                                                    ● <?php echo ucfirst($mov['tipo']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <span class="<?php echo $mov['tipo'] === 'receita' ? 'valor-table-pos' : 'valor-table-neg'; ?>" style="margin-right: 8px;">
                                                    <?php echo $mov['tipo'] === 'receita' ? '+ R$ ' : '- R$ '; ?>
                                                    <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                                </span>
                                                <a href="editar_movimentacao.php?id=<?php echo $mov['id']; ?>" class="btn-icon-action" title="Editar">
                                                    <i data-lucide="pencil" class="lucide-sm"></i>
                                                </a>
                                                <a href="excluir_movimentacao.php?id=<?php echo $mov['id']; ?>" onclick="return confirm('Excluir esta movimentação?');" class="btn-icon-action danger" title="Excluir">
                                                    <i data-lucide="trash-2" class="lucide-sm"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- COLUNA DIREITA -->
                <div>
                    <div class="dashboard-card-box">
                        <div class="box-header-flex">
                            <div>
                                <h3 class="box-title">Despesas por categoria</h3>
                                <p style="font-size: 11px; color: var(--text-muted);"><?php echo $mesAtualNome; ?> de 2026</p>
                            </div>
                            <span class="select-filter-compact" style="font-size: 11px;">Últimos 30 dias</span>
                        </div>

                        <div style="position: relative; height: 180px; margin: 10px 0;">
                            <?php if (empty($valores_chart) || $total_despesas == 0): ?>
                                <p style="text-align: center; color: var(--text-muted); padding-top: 60px; font-size: 12px;">
                                    Nenhuma despesa para exibir no gráfico.
                                </p>
                            <?php else: ?>
                                <canvas id="chartDespesas"></canvas>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($categorias_despesas) && $total_despesas > 0): ?>
                            <div style="margin-top: 16px; border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                                <?php 
                                $cores = ['#0f2738', '#059669', '#3b82f6', '#94a3b8', '#e2e8f0'];
                                $idx = 0;
                                foreach ($categorias_despesas as $cd): 
                                    $porcentagem = round(($cd['total_cat'] / $total_despesas) * 100);
                                    $cor = $cores[$idx % count($cores)] ?? '#059669';
                                    $idx++;
                                ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?php echo $cor; ?>;"></span>
                                            <span style="font-weight: 600; color: var(--text-title);"><?php echo $cd['nome']; ?></span>
                                            <span style="color: var(--text-muted);">R$ <?php echo number_format($cd['total_cat'], 2, ',', '.'); ?></span>
                                        </div>
                                        <span style="font-weight: 700; color: var(--navy-main);"><?php echo $porcentagem; ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div style="margin-top: 28px; padding-top: 14px; border-top: 1px solid var(--border-subtle); text-align: center; font-size: 11px; color: var(--text-muted);">
                <span>© 2026 FinanCia. Todos os direitos reservados.</span>
            </div>
        </main>
    </div>

    <!-- SCRIPTS JS CORRIGIDOS (GRÁFICO + LUCIDE ICONS) -->
    <?php if (!empty($valores_chart) && $total_despesas > 0): ?>
    <script>
        const ctx = document.getElementById('chartDespesas').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels_chart); ?>,
                datasets: [{
                    data: <?php echo json_encode($valores_chart); ?>,
                    backgroundColor: ['#0f2738', '#059669', '#3b82f6', '#94a3b8', '#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '72%'
            }
        });
    </script>
    <?php endif; ?>

    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>