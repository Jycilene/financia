<?php
// 1. Puxa a conexão com o banco de dados
require_once __DIR__ . '/config/database.php';

$mensagem = '';
$tipo_mensagem = '';
$nome_val = '';
$email_val = '';

// 2. Se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = trim($_POST['nome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    $nome_val  = $nome;
    $email_val = $email;

    // Validações no backend
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios!";
        $tipo_mensagem = "erro";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Por favor, informe um endereço de e-mail válido!";
        $tipo_mensagem = "erro";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha deve conter no mínimo 8 caracteres!";
        $tipo_mensagem = "erro";
    } elseif ($senha !== $confirma_senha) {
        $mensagem = "A confirmação de senha não confere com a senha informada!";
        $tipo_mensagem = "erro";
    } else {
        try {
            // 3. Verificação explícita de e-mail duplicado
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt_check->execute([':email' => $email]);

            if ($stmt_check->fetch()) {
                $mensagem = "Este e-mail já está em uso por outro usuário!";
                $tipo_mensagem = "erro";
            } else {
                // Criptografa a senha com segurança (bcrypt)
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Insere com perfil padrão 'user' e status 'ativa'
                $sql = "INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES (:nome, :email, :senha, 'user', 'ativa')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome'  => $nome,
                    ':email' => $email,
                    ':senha' => $senha_hash
                ]);

                $mensagem = "Cadastro realizado com sucesso! Você já pode entrar.";
                $tipo_mensagem = "sucesso";
                $nome_val = '';
                $email_val = '';
            }

        } catch (PDOException $e) {
            // Código '23505' no PostgreSQL = violação de UNIQUE
            if ($e->getCode() == '23505') {
                if (strpos($e->getMessage(), 'usuarios_email_key') !== false || strpos($e->getMessage(), 'email') !== false) {
                    $mensagem = "Este e-mail já está em uso por outro usuário!";
                } else {
                    $mensagem = "Erro de concorrência ou sequência de cadastro. Tente novamente.";
                }
            } else {
                $mensagem = "Erro ao cadastrar: " . $e->getMessage();
            }
            $tipo_mensagem = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Criar Conta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- Cabeçalho do Card com Logo -->
            <div class="auth-header">
                <div class="brand-icon">💰</div>
                <h2>Criar Conta no FinanCia</h2>
                <p>Comece a controlar suas finanças hoje mesmo</p>
            </div>

            <!-- Alerta de Sucesso ou Erro -->
            <?php if (!empty($mensagem)): ?>
                <div class="alerta <?php echo $tipo_mensagem === 'sucesso' ? 'alerta-sucesso' : 'alerta-erro'; ?>">
                    <?php echo $mensagem; ?>
                    <?php if ($tipo_mensagem === 'sucesso'): ?>
                        <br><a href="login.php" style="font-weight: 700; color: var(--receita); text-decoration: underline;">Ir para o Login</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="formCadastro">
                <div class="input-container">
                    <label class="form-label" for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" class="input-field" placeholder="Mariana Costa" value="<?php echo htmlspecialchars($nome_val); ?>" required autofocus>
                </div>

                <div class="input-container">
                    <label class="form-label" for="email">E-mail *</label>
                    <input type="email" id="email" name="email" class="input-field" placeholder="mariana@exemplo.com" value="<?php echo htmlspecialchars($email_val); ?>" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Crie uma senha segura" required>
                </div>

                <button type="submit" class="btn-block">Criar Minha Conta</button>
            </form>

            <!-- Link para voltar ao Login -->
            <div class="auth-footer">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </div>

        </div>
    </div>

</body>
</html>
