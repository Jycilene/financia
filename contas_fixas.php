<?php
// 1. Inicia a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';

// Ação rápida para marcar ocorrência como paga (caso venha da listagem)
if (isset($_GET['pagar'])) {
    $pagamento_id = (int) $_GET['pagar'];
    $data_hoje = date('Y-m-d');
    
    $stmt_p = $pdo->prepare("UPDATE pagamentos_fixas SET status = 'paga', data_pagamento = :dp WHERE id = :id AND usuario_id = :uid");
    $stmt_p->execute([':dp' => $data_hoje, ':id' => $pagamento_id, ':uid' => $usuario_id]);
    
    header("Location: contas_fixas.php");
    exit;
}

// Ação para inativar compromisso
if (isset($_GET['inativar'])) {
    $conta_id = (int) $_GET['inativar'];
    $stmt_in = $pdo->prepare("UPDATE despesas_fixas SET ativo = FALSE WHERE id = :id AND usuario_id = :uid");
    $stmt_in->execute([':id' => $conta_id, ':uid' => $usuario_id]);
    
    header("Location: contas_fixas.php");
    exit;
}

// Busca todas as despesas/compromissos do utilizador
$stmt_contas = $pdo->prepare("
    SELECT df.*, c.nome AS categoria_nome 
    FROM despesas_fixas df 
    JOIN categorias c ON df.categoria_id = c.id 
    WHERE df.usuario_id = :uid 
    ORDER BY df.ativo DESC, df.nome ASC
");
$stmt_contas->execute([':uid' => $usuario_id]);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Contas Fixas e Compromissos</title>
    <link rel="stylesheet" href="css/style.css">
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
                    <a href="dashboard.php" class="nav-item">
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
                    <a href="contas_fixas.php" class="nav-item active">
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
                    <a href="perfil.php" title="Meu perfil" style="text-decoration: none; display: flex; align-items: center; gap: 10px; width: 100%;">
                        <?php if (!empty($foto_usuario)): ?>
                            <img src="<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
                        <?php else: ?>
                            <div class="user-avatar-circle" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%; font-weight: bold; font-size: 14px;"><?php echo $iniciais; ?></div>
                        <?php endif; ?>
                        <div class="user-meta-info" style="overflow: hidden; flex: 1;">
                            <div class="user-name-text" style="font-weight: 600; font-size: 13px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($usuario_nome); ?></div>
                        </div>
                    </a>
                    <a href="logout.php" style="font-size: 11px; color: #dc2626; text-decoration: none; margin-top: 4px; display: inline-block;">Sair</a>
                </div>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="app-main-content">
            
            <header class="app-header" style="margin-bottom: 24px;">
                <div>
                    <h1 class="page-title">Contas Fixas e Compromissos</h1>
                    <p class="page-subtitle">Gerencie suas despesas recorrentes e dívidas parceladas.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i> Painel Admin
                        </a>
                    <?php endif; ?>
                    <a href="nova_conta_fixa.php" class="btn-nova-mov-navy" style="text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="plus" class="lucide-sm"></i> Novo Compromisso
                    </a>
                </div>
            </header>

            <?php if (isset($_GET['sucesso'])): ?>
                <div style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="check-circle" class="lucide-sm"></i> Compromisso cadastrado com sucesso!
                </div>
            <?php endif; ?>

            <div class="dashboard-card-box">
                <div class="box-header-flex" style="margin-bottom: 16px;">
                    <h2 class="box-title">Seus Compromissos Cadastrados</h2>
                </div>

                <div class="table-responsive">
                    <table class="financia-table">
                        <thead>
                            <tr>
                                <th>Compromisso</th>
                                <th>Categoria</th>
                                <th>Tipo</th>
                                <th>Valor / Detalhes</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contas)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        Nenhum compromisso cadastrado ainda. Clique em "+ Novo Compromisso" acima.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contas as $c): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size: 14px; color: var(--text-title);"><?php echo htmlspecialchars($c['nome']); ?></strong>
                                            <?php if (!empty($c['descricao'])): ?>
                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                    <?php echo htmlspecialchars(mb_strimwidth($c['descricao'], 0, 40, "...")); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($c['categoria_nome']); ?></td>
                                        <td>
                                            <?php if (($c['tipo_compromisso'] ?? 'recorrente') === 'parcelada'): ?>
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Parcelada</span>
                                            <?php else: ?>
                                                <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Recorrente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($c['tipo_compromisso'] ?? 'recorrente') === 'parcelada'): ?>
                                                R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?> <span style="font-size: 11px; color: var(--text-muted);">(Parc.)</span>
                                            <?php else: ?>
                                                R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>Todo dia <?php echo $c['dia_vencimento']; ?></td>
                                        <td>
                                            <?php if ($c['ativo']): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Ativo</span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <!-- BOTÃO DE DETALHES -->
                                            <a href="detalhes_conta_fixa.php?id=<?php echo $c['id']; ?>" class="btn-clear-gray" style="text-decoration: none; padding: 6px 10px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; margin-right: 4px;" title="Ver Detalhes e Parcelas">
                                                <i data-lucide="eye" class="lucide-sm"></i> Detalhes
                                            </a>
                                            
                                            <?php if ($c['ativo']): ?>
                                                <a href="contas_fixas.php?inativar=<?php echo $c['id']; ?>" style="color: #dc2626; font-size: 11px; text-decoration: none; padding: 6px 10px; background: #fee2e2; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" onclick="return confirm('Deseja realmente inativar este compromisso?');" title="Inativar">
                                                    <i data-lucide="ban" class="lucide-sm"></i> Inativar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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