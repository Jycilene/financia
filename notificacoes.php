<?php
// notificacoes.php - Central de Notificações do FinanCia
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gerador_notificacoes.php';

// Executa a verificação automática ao carregar a central
verificarEGeraNotificacoes($pdo, $usuario_id);

// Ações via POST (Marcar como lida, Excluir, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'marcar_lida' && isset($_POST['id'])) {
        $notif_id = (int) $_POST['id'];
        $upd = $pdo->prepare("UPDATE notificacoes SET lida = TRUE, data_leitura = CURRENT_TIMESTAMP WHERE id = :id AND usuario_id = :uid");
        $upd->execute([':id' => $notif_id, ':uid' => $usuario_id]);
    } elseif ($acao === 'marcar_todas_lidas') {
        $upd = $pdo->prepare("UPDATE notificacoes SET lida = TRUE, data_leitura = CURRENT_TIMESTAMP WHERE usuario_id = :uid AND lida = FALSE");
        $upd->execute([':uid' => $usuario_id]);
    } elseif ($acao === 'excluir' && isset($_POST['id'])) {
        $notif_id = (int) $_POST['id'];
        $del = $pdo->prepare("DELETE FROM notificacoes WHERE id = :id AND usuario_id = :uid");
        $del->execute([':id' => $notif_id, ':uid' => $usuario_id]);
    }
    
    header("Location: notificacoes.php");
    exit;
}

// Busca todas as notificações do usuário ordenadas por data descrescente
$stmt = $pdo->prepare("SELECT * FROM notificacoes WHERE usuario_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $usuario_id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nome e iniciais para o menu lateral
$partes_nome = explode(' ', trim($_SESSION['usuario_nome']));
$iniciais = strtoupper(substr($partes_nome[0], 0, 1) . (isset($partes_nome[1]) ? substr($partes_nome[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Central de Notificações</title>
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
                    <a href="movimentacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="arrow-left-right"></i></span>
                        <span>Movimentações</span>
                    </a>
                    <a href="contas_fixas.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="calendar-days"></i></span>
                        <span>Contas fixas</span>
                    </a>
                    <a href="notificacoes.php" class="nav-item active">
                        <span class="nav-item-icon"><i data-lucide="bell"></i></span>
                        <span>Notificações</span>
                    </a>
                    <a href="nova_movimentacao.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="circle-plus"></i></span>
                        <span>Nova movimentação</span>
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
            
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                Painel / <strong>Notificações</strong>
            </div>
            
            <header class="app-header" style="margin-bottom: 20px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Central de Notificações</h1>
                    <p class="page-subtitle">Acompanhe avisos de vencimento, contas em atraso e confirmações.</p>
                </div>

                <div class="header-right-tools">
                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i>
                            <span>Painel Admin</span>
                        </a>
                    <?php endif; ?>

                    <!-- SINO DE NOTIFICAÇÕES COM CONTADOR -->
                    <?php
                    $stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = FALSE");
                    $stmt_badge->execute([':uid' => $usuario_id]);
                    $nao_lidas = $stmt_badge->fetchColumn();
                    ?>
                    <a href="notificacoes.php" style="text-decoration: none; position: relative; margin-right: 8px; display: inline-flex; align-items: center;" title="Notificações">
                        <i data-lucide="bell" class="lucide-md" style="color: var(--navy-main);"></i>
                        <?php if ($nao_lidas > 0): ?>
                            <span style="position: absolute; top: -5px; right: -8px; background: #dc2626; color: white; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 10px;">
                                <?php echo $nao_lidas; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <form action="notificacoes.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="acao" value="marcar_todas_lidas">
                        <button type="submit" style="background: var(--navy-main); color: white; border: none; padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="check-check" class="lucide-sm"></i>
                            <span>Marcar todas como lidas</span>
                        </button>
                    </form>

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

            <div class="dashboard-card-box">
                <?php if (empty($notificacoes)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 40px 0; font-size: 13px;">
                        <i data-lucide="sparkles" class="lucide-lg" style="color: var(--emerald-main); margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"></i>
                        Não tem nenhuma notificação no momento. As suas contas estão em dia!
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($notificacoes as $notif): ?>
                            <?php 
                                // Identificação visual por tipo
                                $corBorda = '#cbd5e1';
                                $bgCard = $notif['lida'] ? '#ffffff' : '#f8fafc';
                                $badgeBg = '#e2e8f0';
                                $badgeColor = '#334155';

                                if ($notif['tipo'] === 'atrasada') {
                                    $corBorda = '#dc2626';
                                    $badgeBg = '#fee2e2';
                                    $badgeColor = '#991b1b';
                                } elseif ($notif['tipo'] === 'vencimento_hoje') {
                                    $corBorda = '#d97706';
                                    $badgeBg = '#fef3c7';
                                    $badgeColor = '#92400e';
                                } elseif (strpos($notif['tipo'], 'vencimento_') !== false) {
                                    $corBorda = '#2563eb';
                                    $badgeBg = '#dbeafe';
                                    $badgeColor = '#1e40af';
                                } elseif ($notif['tipo'] === 'pagamento_confirmado') {
                                    $corBorda = '#159A80';
                                    $badgeBg = '#d1fae5';
                                    $badgeColor = '#065f46';
                                }
                            ?>
                            <div style="background: <?php echo $bgCard; ?>; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo $corBorda; ?>; padding: 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                                
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                        <span style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                            <?php echo htmlspecialchars($notif['titulo']); ?>
                                        </span>
                                        <span style="font-size: 11px; color: var(--text-muted);">
                                            <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                        </span>
                                        <?php if (!$notif['lida']): ?>
                                            <span style="background: var(--emerald-main); width: 8px; height: 8px; border-radius: 50%; display: inline-block;" title="Não lida"></span>
                                        <?php endif; ?>
                                    </div>

                                    <p style="font-size: 13px; color: var(--text-main); margin: 0 0 10px 0; line-height: 1.4;">
                                        <?php echo htmlspecialchars($notif['mensagem']); ?>
                                    </p>

                                    <!-- Link de Ação (Se houver pagamento fixo associado) -->
                                    <?php if (!empty($notif['pagamento_fixo_id'])): ?>
                                        <a href="contas_fixas.php" style="font-size: 12px; color: var(--emerald-main); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <span>Ver ocorrência em Contas Fixas</span>
                                            <i data-lucide="arrow-right" class="lucide-sm"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Ações (Marcar lida / Excluir) -->
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <?php if (!$notif['lida']): ?>
                                        <form action="notificacoes.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="acao" value="marcar_lida">
                                            <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" title="Marcar como lida" style="background: none; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px;">
                                                <i data-lucide="check" class="lucide-sm"></i>
                                                <span>Lida</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form action="notificacoes.php" method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja apagar esta notificação?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" title="Eliminar" style="background: none; border: 1px solid #fca5a5; color: #dc2626; padding: 6px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                            <i data-lucide="trash-2" class="lucide-sm"></i>
                                        </button>
                                    </form>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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