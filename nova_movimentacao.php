<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gerador_notificacoes.php'; // Inclui o motor de notificações

// Executa a verificação automática de notificações ao abrir a página
verificarEGeraNotificacoes($pdo, $usuario_id);

$mensagem_erro = '';

// 2. Busca categorias cadastradas no PostgreSQL
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY tipo DESC, nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 3. Processa o envio via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao         = trim($_POST['descricao']);
    $valor             = str_replace(',', '.', trim($_POST['valor']));
    $tipo              = isset($_POST['tipo']) ? $_POST['tipo'] : 'receita';
    $categoria_id      = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $data_movimentacao = $_POST['data_movimentacao'];

    if (empty($descricao) || empty($valor) || empty($tipo) || $categoria_id <= 0 || empty($data_movimentacao)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios!";
    } elseif ($valor <= 0) {
        $mensagem_erro = "Informe um valor maior que zero — exemplo de validação próxima ao campo.";
    } else {
        try {
            $sql = "INSERT INTO movimentacoes (usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) 
                    VALUES (:usuario_id, :categoria_id, :descricao, :valor, :tipo, :data_movimentacao)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id'        => $usuario_id,
                ':categoria_id'      => $categoria_id,
                ':descricao'         => $descricao,
                ':valor'             => $valor,
                ':tipo'              => $tipo,
                ':data_movimentacao' => $data_movimentacao
            ]);

            header("Location: dashboard.php");
            exit;

        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao cadastrar no banco: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Nova Movimentação</title>
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
                    <a href="nova_movimentacao.php" class="nav-item active">
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

        <!-- ÁREA PRINCIPAL -->
        <main class="app-main-content">
            
            <header class="app-header" style="margin-bottom: 20px;">
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                        Movimentações / <strong>Nova movimentação</strong>
                    </div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Nova movimentação</h1>
                    <p class="page-subtitle" style="margin: 0;">
                         Registe uma nova receita ou despesa para manter o seu orçamento atualizado.
                    </p>
                </div>

                <div class="header-right-tools">
                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i>
                            <span>Painel Admin</span>
                        </a>
                    <?php endif; ?>

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

            <form action="" method="POST" id="formMovimentacao">
                
                <div class="form-split-grid">

                    <!-- LADO ESQUERDO: CAMPOS -->
                    <div class="dashboard-card-box">
                        
                        <label class="form-label">TIPO DE LANÇAMENTO *</label>
                        
                        <!-- Seletor em Cards Estilo Banani -->
                        <div class="segmented-radio-grid">
                            <label class="radio-card-option active receita" id="cardReceita">
                                <input type="radio" name="tipo" value="receita" checked style="display: none;">
                                <div class="radio-badge-icon rec" style="display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="trending-up" class="lucide-sm"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 700; color: var(--navy-main);">Receita</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Dinheiro que entra</div>
                                </div>
                                <span class="check-indicator" id="indicatorReceita" style="color: var(--emerald-main); display: flex; align-items: center;">
                                    <i data-lucide="check" class="lucide-sm"></i>
                                </span>
                            </label>

                            <label class="radio-card-option despesa" id="cardDespesa">
                                <input type="radio" name="tipo" value="despesa" style="display: none;">
                                <div class="radio-badge-icon desp" style="display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="trending-down" class="lucide-sm"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 700; color: var(--navy-main);">Despesa</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Dinheiro que sai</div>
                                </div>
                                <span class="check-indicator" id="indicatorDespesa" style="color: var(--text-muted); display: flex; align-items: center;">
                                    <i data-lucide="circle" class="lucide-sm"></i>
                                </span>
                            </label>
                        </div>

                        <!-- Descrição -->
                        <div class="input-container">
                            <label class="form-label" for="descricao">Descrição *</label>
                            <input type="text" id="descricao" name="descricao" class="input-field" placeholder="Ex.: Salário — TechFlow" required autofocus>
                        </div>

                        <!-- Valor e Data Lado a Lado -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="input-container">
                                <label class="form-label" for="valor">Valor (R$) *</label>
                                <input type="number" step="0.01" min="0.01" id="valor" name="valor" class="input-field" placeholder="0.00" required>
                            </div>

                            <div class="input-container">
                                <label class="form-label" for="data_movimentacao">Data *</label>
                                <input type="date" id="data_movimentacao" name="data_movimentacao" class="input-field" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <!-- Categoria com filtro dinâmico -->
                        <div class="input-container">
                            <label class="form-label" for="categoria_id">Categoria *</label>
                            <select id="categoria_id" name="categoria_id" class="input-field" required>
                                <option value="">Selecione uma categoria...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-tipo="<?php echo $cat['tipo']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Alerta de Erro -->
                        <?php if (!empty($mensagem_erro)): ?>
                            <div style="background-color: var(--red-light); color: var(--red-main); border: 1px solid var(--red-border); padding: 12px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="alert-circle" class="lucide-sm"></i>
                                <span><?php echo $mensagem_erro; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Botões -->
                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
                            <button type="submit" class="btn-submit-navy" style="width: auto; padding: 12px 24px; display: inline-flex; align-items: center; gap: 8px;">
                                <i data-lucide="check" class="lucide-sm"></i>
                                <span>Salvar movimentação</span>
                            </button>
                            <a href="dashboard.php" class="btn-clear-gray" style="padding: 12px 20px; font-size: 13px; text-decoration: none;">
                                Cancelar
                            </a>
                        </div>

                    </div>

                    <!-- LADO DIREITO: CARD DE RESUMO EM TEMPO REAL + DICA -->
                    <div>
                        <div class="resumo-card-widget">
                            <h4>Resumo do lançamento</h4>
                            
                            <div class="resumo-row">
                                <span>Tipo</span>
                                <strong id="resumoTipo" style="color: var(--emerald-main);">Receita</strong>
                            </div>
                            <div class="resumo-row">
                                <span>Valor</span>
                                <strong id="resumoValor">R$ 0,00</strong>
                            </div>
                            <div class="resumo-row">
                                <span>Categoria</span>
                                <strong id="resumoCategoria">—</strong>
                            </div>

                            <div class="resumo-divider"></div>

                            <div class="resumo-row" style="font-size: 14px;">
                                <span style="font-weight: 700; color: var(--navy-main);">Impacto no saldo</span>
                                <strong id="resumoImpacto" style="color: var(--emerald-main); font-size: 16px;">+ R$ 0,00</strong>
                            </div>
                        </div>

                        <div class="dica-card-navy">
                            <h5 style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <i data-lucide="lightbulb" class="lucide-sm" style="color: #fbbf24;"></i>
                                <span>Dica FinanCia</span>
                            </h5>
                            <p>
                                Use descrições claras (ex: "Supermercado - Mensal") para facilitar a organização nos relatórios e filtros futuros.
                            </p>
                        </div>
                    </div>

                </div>

            </form>

        </main>

    </div>

    <!-- Script de Interatividade e Resumo em Tempo Real -->
    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        const cardReceita = document.getElementById('cardReceita');
        const cardDespesa = document.getElementById('cardDespesa');
        const radioReceita = cardReceita.querySelector('input');
        const radioDespesa = cardDespesa.querySelector('input');

        const catSelect = document.getElementById('categoria_id');
        const valInput = document.getElementById('valor');

        const rTipo = document.getElementById('resumoTipo');
        const rValor = document.getElementById('resumoValor');
        const rCat = document.getElementById('resumoCategoria');
        const rImpacto = document.getElementById('resumoImpacto');
        const indReceita = document.getElementById('indicatorReceita');
        const indDespesa = document.getElementById('indicatorDespesa');

        function filtrarCategorias(tipo) {
            const options = catSelect.querySelectorAll('option');
            options.forEach(opt => {
                const optTipo = opt.getAttribute('data-tipo');
                if (!optTipo) return;
                if (optTipo === tipo) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });

            const sel = catSelect.selectedOptions[0];
            if (sel && sel.style.display === 'none') {
                catSelect.value = '';
            }
        }

        function setTipo(tipo) {
            if (tipo === 'receita') {
                radioReceita.checked = true;
                cardReceita.classList.add('active', 'receita');
                cardDespesa.classList.remove('active', 'despesa');
                indReceita.innerHTML = '<i data-lucide="check" class="lucide-sm"></i>';
                indReceita.style.color = 'var(--emerald-main)';
                indDespesa.innerHTML = '<i data-lucide="circle" class="lucide-sm"></i>';
                indDespesa.style.color = 'var(--text-muted)';

                rTipo.textContent = 'Receita';
                rTipo.style.color = 'var(--emerald-main)';
                filtrarCategorias('receita');
            } else {
                radioDespesa.checked = true;
                cardDespesa.classList.add('active', 'despesa');
                cardReceita.classList.remove('active', 'receita');
                indDespesa.innerHTML = '<i data-lucide="check" class="lucide-sm"></i>';
                indDespesa.style.color = 'var(--navy-main)';
                indReceita.innerHTML = '<i data-lucide="circle" class="lucide-sm"></i>';
                indReceita.style.color = 'var(--text-muted)';

                rTipo.textContent = 'Despesa';
                rTipo.style.color = 'var(--red-main)';
                filtrarCategorias('despesa');
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            updateResumo();
        }

        cardReceita.addEventListener('click', () => setTipo('receita'));
        cardDespesa.addEventListener('click', () => setTipo('despesa'));

        function updateResumo() {
            const val = parseFloat(valInput.value) || 0;
            const formatted = val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            const isReceita = radioReceita.checked;

            rValor.textContent = 'R$ ' + formatted;
            rCat.textContent = catSelect.selectedOptions[0] ? catSelect.selectedOptions[0].text : '—';

            if (isReceita) {
                rImpacto.textContent = '+ R$ ' + formatted;
                rImpacto.style.color = 'var(--emerald-main)';
            } else {
                rImpacto.textContent = '- R$ ' + formatted;
                rImpacto.style.color = 'var(--navy-main)';
            }
        }

        valInput.addEventListener('input', updateResumo);
        catSelect.addEventListener('change', updateResumo);

        // Inicializa
        setTipo('receita');
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>