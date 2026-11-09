<?php
// 1. Abre a sessão atual
session_start();

// 2. Destrói todas as informações da sessão (desloga o usuário)
session_destroy();

// 3. Redireciona para a tela de login
header("Location: login.php");
exit;
