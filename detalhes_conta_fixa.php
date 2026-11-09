<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';
date_default_timezone_set('America/Recife');

$conta_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($conta_id <= 0) {
    header("Location: contas_fixas.php");
    exit;
}

// 2. Ação para marcar parcela específica como paga
if (isset($_GET['pagar_parcela'])) {
    $parcela_id = (int) $_GET['pagar_parcela'];
    $data_hoje = date('Y-m-d');

    // Verifica se a parcela pertence ao utilizador
    $stmt_chk = $pdo->prepare("SELECT id FROM pagamentos_fixas WHERE id = :pid AND usuario_id = :uid");
    $stmt_chk->execute([':pid' => $parcela_id, ':uid' => $usuario_id]);
    
    if ($stmt_chk->fetch()) {
        $stmt_up = $pdo->prepare("UPDATE pagamentos_fixas SET status = 'paga', data_pagamento = :dp WHERE id = :pid");
        $stmt_up->execute([':dp' => $data_hoje, ':pid' => $parcela_id]);
    }
    header("Location: detalhes_conta_fixa.php?id=" . $conta_id);
    exit;
}

// 3. Busca os detalhes da conta fixa / dívida
$stmt_conta = $pdo->prepare("
    SELECT df.*, c.nome AS categoria_nome 
    FROM despesas_fixas df 
    JOIN categorias c ON df.categoria_id = c.id 
    WHERE df.id = :id AND df.usuario_id = :uid
");
$stmt_conta->execute([':id' => $conta_id, ':uid' => $usuario_id]);
$conta = $stmt_conta->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
    header("Location: contas_fixas.php");
    exit;
}

// 4. Busca o histórico de pagamentos/parcelas associadas
$stmt_parcelas = $pdo->prepare("
    SELECT * FROM pagamentos_fixas 
    WHERE despesa_fixa_id = :did AND usuario_id = :uid 
    ORDER BY numero_parcela ASC, data_vencimento ASC
");
$stmt_parcelas->execute([':did' => $conta_id, ':uid' => $usuario_id]);
$parcelas = $stmt_parcelas->fetchAll(PDO::FETCH_ASSOC);

// 5. Cálculo dos Indicadores de Progresso
$total_parcelas = count($parcelas);
$pagas_count = 0;
$valor_pago = 0.0;
$valor_restante = 0.0;

foreach ($parcelas as $p) {
    $val = (float) $p['valor'];
    if ($p['status'] === 'paga') {
        $pagas_count++;
        $valor_pago += $val;
    } else {
        $valor_restante += $val;
    }
}

$percentual = ($total_parcelas > 0) ? ($pagas_count / $total_parcelas) * 100 : 0;
$valor_total_divida = ($conta['tipo_compromisso'] === 'parcelada') ? (float) $conta['valor_total'] : ($valor_pago + $valor_restante);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Detalhes do Compromisso</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-wrapper">
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

        <main class="app-main-content">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                <a href="contas_fixas.php" style="text-decoration: none; color: inherit;">Contas fixas</a> / <strong>Detalhes do Compromisso</strong>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;"><?php echo htmlspecialchars($conta['nome']); ?></h1>
                    <p class="page-subtitle">Categoria: <strong><?php echo htmlspecialchars($conta['categoria_nome']); ?></strong> | Tipo: <strong><?php echo ucfirst($conta['tipo_compromisso']); ?></strong></p>
                </div>
                <div>
                    <div style="display: flex; gap: 8px;">
                        <a href="editar_conta_fixa.php?id=<?php echo $conta_id; ?>" class="btn-hero-emerald" style="text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="pencil" class="lucide-sm"></i> Editar
                        </a>
                        <a href="contas_fixas.php" class="btn-clear-gray" style="text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="arrow-left" class="lucide-sm"></i> Voltar à Lista
                        </a>
                    </div>
                </div>
            </div>

            <!-- BLOCO DE PROGRESSO (APENAS PARA PARCELADAS) -->
            <?php if ($conta['tipo_compromisso'] === 'parcelada'): ?>
                <div class="dashboard-card-box" style="margin-bottom: 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="font-size: 16px; color: var(--navy-main); font-weight: 700;">Progresso da Dívida</h3>
                        <span style="font-size: 14px; font-weight: 700; color: var(--navy-main);"><?php echo $pagas_count; ?> de <?php echo $total_parcelas; ?> parcelas pagas</span>
                    </div>

                    <!-- Barra de Progresso Visual CSS -->
                    <div style="background: #cbd5e1; height: 12px; border-radius: 6px; overflow: hidden; margin-bottom: 16px;">
                        <div style="background: var(--emerald-main); width: <?php echo $percentual; ?>%; height: 100%; transition: width 0.4s ease;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                        <div style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Conclusão</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--emerald-main);"><?php echo number_format($percentual, 2, ',', '.'); ?>%</div>
                        </div>
                        <div style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Valor Já Pago</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--navy-main);">R$ <?php echo number_format($valor_pago, 2, ',', '.'); ?></div>
                        </div>
                        <div style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Valor Restante</div>
                            <div style="font-size: 18px; font-weight: 700; color: #dc2626;">R$ <?php echo number_format($valor_restante, 2, ',', '.'); ?></div>
                        </div>
                        <div style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Valor Total</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--navy-main);">R$ <?php echo number_format($valor_total_divida, 2, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- INFORMAÇÕES GERAIS E DESCRIÇÃO -->
            <div class="dashboard-card-box" style="margin-bottom: 24px;">
                <h3 style="font-size: 15px; color: var(--navy-main); font-weight: 700; margin-bottom: 12px;">Informações do Compromisso</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 12px; color: var(--text-muted); display: block;">Dia de Vencimento</span>
                        <strong style="font-size: 14px; color: var(--text-title);">Todo dia <?php echo $conta['dia_vencimento']; ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--text-muted); display: block;">Data de Início</span>
                        <strong style="font-size: 14px; color: var(--text-title);"><?php echo date('d/m/Y', strtotime($conta['data_inicio'])); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--text-muted); display: block;">Situação Atual</span>
                        <strong style="font-size: 14px; color: <?php echo $conta['ativo'] ? 'var(--emerald-main)' : '#dc2626'; ?>;">
                            <?php echo $conta['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </strong>
                    </div>
                </div>

                <?php if (!empty($conta['descricao'])): ?>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px;">
                        <span style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Descrição Completa</span>
                        <p style="font-size: 13px; color: var(--text-title); line-height: 1.5;"><?php echo nl2br(htmlspecialchars($conta['descricao'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- HISTÓRICO DE PARCELAS / OCORRÊNCIAS -->
            <div class="dashboard-card-box">
                <h3 style="font-size: 15px; color: var(--navy-main); font-weight: 700; margin-bottom: 16px;">Histórico de Parcelas e Ocorrências</h3>
                
                <div class="table-responsive">
                    <table class="financia-table">
                        <thead>
                            <tr>
                                <th>Parcela / Referência</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data Pagamento</th>
                                <th style="text-align: right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parcelas)): ?>
                                <tr><td colspan="6" style="text-align: center; padding: 20px;">Nenhuma parcela registada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($parcelas as $p): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo $p['numero_parcela'] ? "Parcela {$p['numero_parcela']}/{$total_parcelas}" : "Mensal ({$p['mes_referencia']}/{$p['ano_referencia']})"; ?>
                                            </strong>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($p['data_vencimento'])); ?></td>
                                        <td>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($p['status'] === 'paga'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Paga</span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $p['data_pagamento'] ? date('d/m/Y', strtotime($p['data_pagamento'])) : '-'; ?></td>
                                        <td style="text-align: right;">
                                            <?php if ($p['status'] !== 'paga'): ?>
                                                <a href="detalhes_conta_fixa.php?id=<?php echo $conta_id; ?>&pagar_parcela=<?php echo $p['id']; ?>" style="background: var(--emerald-main); color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i data-lucide="check" class="lucide-xs"></i> Marcar como Paga
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--emerald-main); font-weight: 600; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i data-lucide="check-circle-2" class="lucide-xs"></i> Liquidada
                                                </span>
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