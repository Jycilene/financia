<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gerador_notificacoes.php'; // Inclui o motor de notificações

// Executa a verificação automática de notificações ao abrir a página
verificarEGeraNotificacoes($pdo, $usuario_id);

// 2. Busca categorias para o filtro
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 3. Filtros
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
$total_encontrados = count($movimentacoes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Movimentações</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="app-wrapper">

      <!-- SIDEBAR -->
        <aside class="app-sidebar">
            <div class="sidebar-top">
                <a href="dashboard.php" class="brand brand-dark sidebar-brand" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <div class="brand-icon"><i data-lucide="check"></i></div>
                    <span class="brand-text">FinanCia</span>
                </a>

                <div class="sidebar-section-title">Menu Principal</div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span>Visão geral</span>
                    </a>
                    <a href="movimentacoes.php" class="nav-item active">
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

            <div class="sidebar-bottom">
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

        <!-- ÁREA PRINCIPAL -->
        <main class="app-main-content">
            
            <header class="app-header">
                <div>
                    <h1 class="page-title">Movimentações</h1>
                    <p class="page-subtitle">Consulte, filtre e gerencie todas as suas entradas e saídas financeiras num só lugar.</p>
                </div>

                <div class="header-right-tools">
                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i> Painel Admin
                        </a>
                    <?php endif; ?>

                    <!-- SINO DE NOTIFICAÇÕES COM CONTADOR -->
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

            <!-- CARD COMPLETO DE TABELA -->
            <div class="dashboard-card-box">
                
                <!-- Barra de Filtros Completa -->
                <form action="movimentacoes.php" method="GET" style="display: flex; gap: 10px; margin-bottom: 22px; flex-wrap: wrap; align-items: center;">
                    <div class="search-input-box" style="flex: 1; min-width: 200px; position: relative;">
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex; align-items: center;">
                            <i data-lucide="search" class="lucide-sm"></i>
                        </span>
                        <input type="text" name="busca" placeholder="Pesquisar por descrição..." value="<?php echo htmlspecialchars($filtro_busca); ?>" style="width: 100%;">
                    </div>

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
                        <a href="movimentacoes.php" class="btn-clear-gray" style="text-decoration: none;">Limpar</a>
                    <?php endif; ?>

                    <a href="nova_movimentacao.php" class="btn-hero-emerald" style="padding: 8px 16px; font-size: 13px; margin-left: auto; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="plus" class="lucide-sm"></i> Nova movimentação
                    </a>
                </form>

                <!-- Tabela -->
                <div class="table-responsive">
                    <table class="financia-table">
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th style="text-align: right;">Valor · Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimentacoes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        Nenhuma movimentação encontrada.
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
                                                <strong><?php echo htmlspecialchars($mov['descricao']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-categoria-pill" style="margin-left: 0;">
                                                <?php echo htmlspecialchars($mov['categoria_nome']); ?>
                                            </span>
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
                                                
                                                $mesPt = $mesesAbreviados[$mesIngles] ?? $mesIngles;
                                                echo "{$dia} {$mesPt} {$ano}";
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

                <!-- Paginação Ilustrativa no Rodapé -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border-subtle); font-size: 12px; color: var(--text-muted);">
                    <div>Mostrando <?php echo $total_encontrados; ?> movimentações</div>
                    <div style="display: flex; gap: 4px;">
                        <button class="select-filter-compact" style="cursor: pointer;"><i data-lucide="chevron-left" class="lucide-xs"></i></button>
                        <button class="btn-filter-navy" style="padding: 4px 10px;">1</button>
                        <button class="select-filter-compact" style="cursor: pointer;"><i data-lucide="chevron-right" class="lucide-xs"></i></button>
                    </div>
                </div>

            </div>

        </main>

    </div>

    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    </script>
</body>
</html>