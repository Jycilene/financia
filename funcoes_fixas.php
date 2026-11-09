<?php
date_default_timezone_set('America/Recife');

function garantirOcorrenciasMesAtual($pdo, $usuario_id) {
    $mesAtual = (int) date('m');
    $anoAtual = (int) date('Y');

    // 1. Buscar todas as despesas fixas ativas do usuário
    $stmt = $pdo->prepare("SELECT * FROM despesas_fixas WHERE usuario_id = :usuario_id AND ativo = TRUE");
    $stmt->execute([':usuario_id' => $usuario_id]);
    $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($despesas as $despesa) {
        $dia = (int) $despesa['dia_vencimento'];
        
        // Tratar meses curtos (ex: dia 31 em Fevereiro vira o último dia do mês)
        $ultimoDiaMes = (int) date('t', strtotime("{$anoAtual}-{$mesAtual}-01"));
        $diaReal = min($dia, $ultimoDiaMes);
        $dataVencimento = sprintf('%04d-%02d-%02d', $anoAtual, $mesAtual, $diaReal);

        // 2. Verificar se já existe ocorrência para este mês/ano
        $check = $pdo->prepare("SELECT id FROM pagamentos_fixas WHERE despesa_fixa_id = :id AND mes_referencia = :mes AND ano_referencia = :ano");
        $check->execute([
            ':id' => $despesa['id'],
            ':mes' => $mesAtual,
            ':ano' => $anoAtual
        ]);

        if (!$check->fetch()) {
            // 3. Inserir a ocorrência pendente do mês
            $insert = $pdo->prepare("INSERT INTO pagamentos_fixas (despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, status) VALUES (:did, :uid, :mes, :ano, :val, :venc, 'pendente')");
            $insert->execute([
                ':did' => $despesa['id'],
                ':uid' => $usuario_id,
                ':mes' => $mesAtual,
                ':ano' => $anoAtual,
                ':val' => $despesa['valor'],
                ':venc' => $dataVencimento
            ]);
        }
    }
}
?>