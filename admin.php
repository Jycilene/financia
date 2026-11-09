<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';

// TRAVA DE SEGURANÇA MÁXIMA NO SERVIDOR (Apenas Admin)
if (!$eh_admin) {
    header("Location: dashboard.php");
    exit;
}

// 2. Busca métricas globais da plataforma (sem expor dados privados)
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_movimentacoes = $pdo->query("SELECT COUNT(*) FROM movimentacoes")->fetchColumn();
$volume_total = (float) $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM movimentacoes")->fetchColumn();

// 3. Busca lista de usuários cadastrados
$sql_usuarios = "SELECT id, nome, email, perfil, status, foto_perfil, criado_em 
                 FROM usuarios 
                 ORDER BY id ASC";
$stmt_users = $pdo->query($sql_usuarios);
$lista_usuarios = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Painel Administrativo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="app-wrapper">

        <!-- SIDEBAR MODO ADMINISTRADOR -->
        <aside class="app-sidebar">
            <div class="sidebar-top">
                <div class="brand brand-dark sidebar-brand">
                    <div class="brand-icon"><i data-lucide="check"></i></div>
                    <span class="brand-text">FinanCia</span>
                </div>

                <div class="sidebar-section-title">Administração</div>
                <nav class="sidebar-nav">
                    <a href="admin.php" class="nav-item active">
                        <span class="nav-item-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span>Visão geral</span>
                    </a>
                    <a href="admin.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="users"></i></span>
                        <span>Usuários</span>
                    </a>
                    <a href="dashboard.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="arrow-left"></i></span>
                        <span>Voltar ao painel pessoal</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-bottom">
                <div class="sidebar-savings-card" style="background: rgba(255, 255, 255, 0.04);">
                    <div class="savings-label">Modo administrador</div>
                    <div class="savings-desc">Gerencie contas sem ver dados financeiros privados.</div>
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
                            <div class="user-name-text" style="font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($usuario_nome); ?> · Admin</div>
                        </a>
                        <a href="logout.php" class="btn-sidebar-sair" style="font-size: 11px; color: #dc2626; text-decoration: none;">Sair</a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL -->
        <main class="app-main-content">
            
            <!-- Cabeçalho -->
            <header class="app-header">
                <div>
                    <h1 class="page-title">Painel Administrativo</h1>
                    <p class="page-subtitle">Indicadores gerais e contas — sem acesso a dados financeiros privados dos usuários.</p>
                </div>

                <div class="header-right-tools">
                    <a href="dashboard.php" class="btn-clear-gray" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="arrow-left" class="lucide-sm"></i> Voltar ao meu painel
                    </a>

                    <!-- Badge de Verificação de Servidor -->
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
                        <span class="badge-admin-tag">ADMIN</span>
                        <a href="logout.php" style="font-size: 12px; color: var(--text-muted); margin-left: 4px;">Sair</a>
                    </div>
                </div>
            </header>

            <!-- 4 CARDS DE KPIS DA PLATAFORMA -->
            <section class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Usuários cadastrados</div>
                        <div class="kpi-value"><?php echo $total_usuarios; ?></div>
                        <div class="kpi-subtext">+1 novo hoje</div>
                    </div>
                    <div class="kpi-icon-pill navy"><i data-lucide="users"></i></div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Movimentações registradas</div>
                        <div class="kpi-value"><?php echo $total_movimentacoes; ?></div>
                        <div class="kpi-subtext muted">Registros globais na plataforma</div>
                    </div>
                    <div class="kpi-icon-pill emerald"><i data-lucide="arrow-left-right"></i></div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Novos usuários · mês</div>
                        <div class="kpi-value"><?php echo $total_usuarios; ?></div>
                        <div class="kpi-subtext">+100% vs. mês anterior</div>
                    </div>
                    <div class="kpi-icon-pill navy"><i data-lucide="trending-up"></i></div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label-row">Volume total plataforma</div>
                        <div class="kpi-value">R$ <?php echo number_format($volume_total, 2, ',', '.'); ?></div>
                        <div class="kpi-subtext muted">Total transacionado</div>
                    </div>
                    <div class="kpi-icon-pill emerald"><i data-lucide="wallet"></i></div>
                </div>
            </section>

            <!-- CONTEÚDO: TABELA DE USUÁRIOS + COLUNA DE PRIVACIDADE -->
            <div class="dashboard-grid-content" style="grid-template-columns: 1.6fr 1fr;">

                <!-- TABELA DE USUÁRIOS -->
                <div class="dashboard-card-box">
                    <div class="box-header-flex">
                        <div>
                            <h2 class="box-title">Gerenciamento de usuários</h2>
                            <p style="font-size: 11px; color: var(--text-muted);">Contas e permissões — sem acesso a dados financeiros privados.</p>
                        </div>
                        <div class="search-input-box">
                            <input type="text" id="filtroUsuarios" placeholder="Buscar por nome ou e-mail..." style="width: 260px;" onkeyup="filtrarUsuarios()">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="financia-table" id="tabelaUsuarios">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Perfil</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_usuarios as $usr): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if (!empty($usr['foto_perfil']) && file_exists(__DIR__ . '/' . $usr['foto_perfil'])): ?>
                                                    <img src="<?php echo htmlspecialchars($usr['foto_perfil']); ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-subtle);">
                                                <?php else: ?>
                                                    <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 12px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%;">
                                                        <?php echo strtoupper(substr($usr['nome'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--navy-main);"><?php echo htmlspecialchars($usr['nome']); ?></div>
                                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($usr['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-badge <?php echo $usr['perfil'] === 'admin' ? 'admin' : 'user'; ?>">
                                                <?php echo ucfirst($usr['perfil']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $usr['status'] === 'ativa' ? 'ativa' : ($usr['status'] === 'pendente' ? 'pendente' : 'desativada'); ?>">
                                                <?php echo ucfirst($usr['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 12px;">
                                            <?php echo !empty($usr['criado_em']) ? date('d/m/Y', strtotime($usr['criado_em'])) : '—'; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button class="btn-icon-action" title="Visualizar conta"><i data-lucide="eye" class="lucide-sm"></i></button>
                                            <button class="btn-icon-action" title="Permissões"><i data-lucide="pencil" class="lucide-sm"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- COLUNA LATERAL DE AUDITORIA & PRIVACIDADE -->
                <div>
                    <!-- Mini Gráfico de Cadastros -->
                    <div class="dashboard-card-box" style="margin-bottom: 20px;">
                        <h3 class="box-title" style="margin-bottom: 4px;">Novos cadastros · 12 semanas</h3>
                        <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 16px;">Evolução de novas contas criadas</p>
                        
                        <div style="display: flex; align-items: flex-end; gap: 6px; height: 80px; padding: 10px 0;">
                            <div style="flex:1; background: #e2e8f0; height: 35%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 50%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 45%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 60%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 40%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 70%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 55%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 80%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 65%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 75%; border-radius: 3px;"></div>
                            <div style="flex:1; background: #e2e8f0; height: 90%; border-radius: 3px;"></div>
                            <div style="flex:1; background: var(--emerald-main); height: 100%; border-radius: 3px;"></div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 8px;">
                            Pico na última semana · <?php echo $total_usuarios; ?> cadastros ativos
                        </div>
                    </div>

                    <!-- Card de Privacidade por Design -->
                    <div class="dica-card-navy">
                        <h5 style="display: flex; align-items: center; gap: 6px;"><i data-lucide="shield-check" class="lucide-md"></i> Privacidade por design</h5>
                        <p style="margin-bottom: 16px;">
    Gerenciamento seguro de acessos. Os dados e saldos financeiros dos usuários são protegidos e visíveis exclusivamente por eles.
</p>
                        <button style="background: white; color: var(--navy-main); border: none; padding: 10px 16px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 700; width: 100%; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <i data-lucide="history" class="lucide-sm"></i> Ver trilha de auditoria
                        </button>
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

    function filtrarUsuarios() {
        const input = document.getElementById('filtroUsuarios');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('tabelaUsuarios');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const text = tr[i].textContent || tr[i].innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
    </script>
</body>
</html>
