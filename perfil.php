<?php
// perfil.php - Perfil Profissional do Utilizador
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gerador_notificacoes.php';

verificarEGeraNotificacoes($pdo, $usuario_id);

$mensagem_sucesso = '';
$mensagem_erro = '';

// Busca dados atuais do utilizador
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt_user->execute([':id' => $usuario_id]);
$usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Processamento de Ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // 1. Atualizar Foto de Perfil
    if ($acao === 'atualizar_foto' && isset($_FILES['foto_perfil'])) {
        $arquivo = $_FILES['foto_perfil'];

        if ($arquivo['error'] === UPLOAD_ERR_OK) {
            $tamanho_maximo = 3 * 1024 * 1024; // 3MB

            if ($arquivo['size'] > $tamanho_maximo) {
                $mensagem_erro = "A imagem é muito grande! O tamanho máximo permitido é de 3MB.";
            } else {
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                // Validação de MIME Type real
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_real = finfo_file($finfo, $arquivo['tmp_name']);
                finfo_close($finfo);

                $mimes_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
                $info_imagem = @getimagesize($arquivo['tmp_name']);

                if (in_array($extensao, $extensoes_permitidas) && in_array($mime_real, $mimes_permitidos) && $info_imagem !== false) {
                    $novo_nome = 'perfil_' . $usuario_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
                    $pasta_destino = __DIR__ . '/uploads/perfil';
                    $destino = $pasta_destino . '/' . $novo_nome;

                    if (!is_dir($pasta_destino)) {
                        mkdir($pasta_destino, 0755, true);
                    }

                    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                        // Remove foto antiga se existir fisicamente
                        if (!empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/' . $usuario['foto_perfil'])) {
                            @unlink(__DIR__ . '/' . $usuario['foto_perfil']);
                        }

                        $caminho_banco = 'uploads/perfil/' . $novo_nome;
                        $upd = $pdo->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id = :id");
                        $upd->execute([':foto' => $caminho_banco, ':id' => $usuario_id]);

                        $_SESSION['usuario_foto'] = $caminho_banco;
                        $usuario['foto_perfil'] = $caminho_banco;
                        $foto_usuario = $caminho_banco;
                        $mensagem_sucesso = "Foto de perfil atualizada com sucesso!";
                    } else {
                        $mensagem_erro = "Erro ao guardar a imagem no servidor. Verifique as permissões de escrita.";
                    }
                } else {
                    $mensagem_erro = "Formato de arquivo inválido! Envie apenas imagens JPG, PNG ou WEBP válidas.";
                }
            }
        } elseif ($arquivo['error'] === UPLOAD_ERR_INI_SIZE || $arquivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            $mensagem_erro = "O arquivo excede o limite de tamanho permitido pelo servidor.";
        } else {
            $mensagem_erro = "Por favor, selecione um arquivo de imagem válido para enviar.";
        }
    }

    // 2. Remover Foto de Perfil
    elseif ($acao === 'remover_foto') {
        if (!empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/' . $usuario['foto_perfil'])) {
            @unlink(__DIR__ . '/' . $usuario['foto_perfil']);
        }
        $upd = $pdo->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = :id");
        $upd->execute([':id' => $usuario_id]);

        $_SESSION['usuario_foto'] = null;
        $usuario['foto_perfil'] = null;
        $foto_usuario = null;
        $mensagem_sucesso = "Foto de perfil removida com sucesso!";
    }

    // 3. Atualizar Dados Pessoais (Nome e E-mail)
    elseif ($acao === 'atualizar_dados') {
        $novo_nome = trim($_POST['nome'] ?? '');
        $novo_email = trim($_POST['email'] ?? '');

        if (!empty($novo_nome) && !empty($novo_email)) {
            // Verifica duplicidade de e-mail com outro usuário
            $check_email = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $check_email->execute([':email' => $novo_email, ':id' => $usuario_id]);

            if ($check_email->fetch()) {
                $mensagem_erro = "Este e-mail já está em uso por outro usuário!";
            } else {
                $stmt_upd = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id");
                $stmt_upd->execute([':nome' => $novo_nome, ':email' => $novo_email, ':id' => $usuario_id]);

                $_SESSION['usuario_nome'] = $novo_nome;
                $_SESSION['usuario_email'] = $novo_email;
                $usuario['nome'] = $novo_nome;
                $usuario['email'] = $novo_email;
                $usuario_nome = $novo_nome;

                $partes_nome = explode(' ', trim($usuario_nome));
                $primeiro_nome = $partes_nome[0] ?? 'Usuário';
                $iniciais = strtoupper(substr($primeiro_nome, 0, 1) . (isset($partes_nome[1]) ? substr($partes_nome[1], 0, 1) : ''));

                $mensagem_sucesso = "Dados pessoais atualizados com sucesso!";
            }
        } else {
            $mensagem_erro = "Preencha todos os campos obrigatórios.";
        }
    }

    // 4. Alterar Palavra-passe
    elseif ($acao === 'alterar_senha') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        if (!empty($senha_atual) && !empty($nova_senha)) {
            if ($nova_senha === $confirma_senha) {
                $senha_valida = password_verify($senha_atual, $usuario['senha']) || ($senha_atual === $usuario['senha']);

                if ($senha_valida) {
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt_pwd = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                    $stmt_pwd->execute([':senha' => $nova_senha_hash, ':id' => $usuario_id]);
                    $mensagem_sucesso = "Palavra-passe alterada com sucesso!";
                } else {
                    $mensagem_erro = "A palavra-passe atual está incorreta.";
                }
            } else {
                $mensagem_erro = "A nova palavra-passe e a confirmação não coincidem.";
            }
        } else {
            $mensagem_erro = "Preencha os campos de palavra-passe.";
        }
    }
}

$foto_atual = !empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/' . $usuario['foto_perfil']) ? $usuario['foto_perfil'] : null;

$stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = FALSE AND excluida = FALSE");
$stmt_badge->execute([':uid' => $usuario_id]);
$nao_lidas = $stmt_badge->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Meu Perfil</title>
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
                    <a href="notificacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="bell"></i></span>
                        <span>Notificações</span>
                    </a>
                    <a href="nova_movimentacao.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="circle-plus"></i></span>
                        <span>Nova movimentação</span>
                    </a>
                    <a href="perfil.php" class="nav-item active">
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
                        <?php if (!empty($foto_atual)): ?>
                            <img src="<?php echo htmlspecialchars($foto_atual); ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
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
            <header class="app-header">
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Configurações / <strong>Meu perfil</strong></div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Configurações da Conta</h1>
                    <p class="page-subtitle">Gerencie as suas informações pessoais, fotografia e credenciais de segurança.</p>
                </div>

                <div class="header-right-tools">
                    <?php if ($eh_admin): ?>
                        <a href="admin.php" class="btn-admin-pill" title="Painel Administrativo" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="shield-check" class="lucide-sm"></i>
                            <span>Painel Admin</span>
                        </a>
                    <?php endif; ?>

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
                            <?php if (!empty($foto_atual)): ?>
                                <img src="<?php echo htmlspecialchars($foto_atual); ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
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

            <?php if (!empty($mensagem_sucesso)): ?>
                <div style="background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="check-circle" class="lucide-sm"></i>
                    <span><?php echo $mensagem_sucesso; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem_erro)): ?>
                <div style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i>
                    <span><?php echo $mensagem_erro; ?></span>
                </div>
            <?php endif; ?>

            <!-- BLOCO PRINCIPAL UNIFICADO (PROFISSIONAL) -->
            <div class="dashboard-card-box" style="max-width: 900px; padding: 30px;">
                
                <!-- CABEÇALHO DO PERFIL (Foto + Nome Rápido) -->
                <div style="display: flex; align-items: center; gap: 20px; padding-bottom: 25px; border-bottom: 1px solid #e5e7eb; margin-bottom: 25px;">
                    <div>
                        <?php if ($foto_atual): ?>
                            <img src="<?php echo $foto_atual; ?>" alt="Foto de perfil" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--navy-main);">
                        <?php else: ?>
                            <div class="user-avatar-circle" style="width: 80px; height: 80px; font-size: 28px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%;"><?php echo $iniciais; ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <h2 style="margin: 0; font-size: 18px; color: var(--navy-main);"><?php echo htmlspecialchars($usuario['nome']); ?></h2>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($usuario['email']); ?></p>
                    </div>
                    <div>
                        <!-- Formulário compacto para carregar foto -->
                        <form action="perfil.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="acao" value="atualizar_foto">
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" onchange="this.form.submit()">
                            <label for="foto_perfil" class="btn-submit-navy" style="cursor: pointer; padding: 8px 16px; font-size: 13px; margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                                <i data-lucide="camera" class="lucide-sm"></i>
                                <span>Alterar Foto</span>
                            </label>
                            <?php if ($foto_atual): ?>
                                <button type="submit" formaction="perfil.php" name="acao" value="remover_foto" style="background: none; border: 1px solid #ef4444; color: #ef4444; padding: 7px 12px; border-radius: 6px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" title="Remover foto">
                                    <i data-lucide="trash-2" class="lucide-sm"></i>
                                    <span>Remover</span>
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- FORMULÁRIO 1: DADOS PESSOAIS -->
                <form action="perfil.php" method="POST" style="margin-bottom: 30px;">
                    <input type="hidden" name="acao" value="atualizar_dados">
                    <h3 style="font-size: 15px; color: var(--navy-main); margin-bottom: 15px; border-left: 3px solid var(--navy-main); padding-left: 8px;">Informações Pessoais</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div class="input-container" style="margin: 0;">
                            <label class="form-label" for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" class="input-field" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        <div class="input-container" style="margin: 0;">
                            <label class="form-label" for="email">Endereço de E-mail</label>
                            <input type="email" id="email" name="email" class="input-field" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit-navy" style="padding: 9px 20px; font-size: 13px; width: auto; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="save" class="lucide-sm"></i>
                        <span>Guardar Dados Pessoais</span>
                    </button>
                </form>

                <!-- FORMULÁRIO 2: SEGURANÇA / PALAVRA-PASSE -->
                <form action="perfil.php" method="POST">
                    <input type="hidden" name="acao" value="alterar_senha">
                    <h3 style="font-size: 15px; color: var(--navy-main); margin-bottom: 15px; border-left: 3px solid var(--navy-main); padding-left: 8px;">Segurança da Conta</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="input-container" style="margin: 0;">
                            <label class="form-label" for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" class="input-field" required>
                        </div>
                        <div class="input-container" style="margin: 0;">
                            <label class="form-label" for="nova_senha">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" class="input-field" required>
                        </div>
                        <div class="input-container" style="margin: 0;">
                            <label class="form-label" for="confirma_senha">Confirmar Nova senha</label>
                            <input type="password" id="confirma_senha" name="confirma_senha" class="input-field" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit-navy" style="padding: 9px 20px; font-size: 13px; width: auto; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="key" class="lucide-sm"></i>
                        <span>Atualizar Senha</span>
                    </button>
                </form>

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