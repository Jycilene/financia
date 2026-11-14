<?php
// includes/gerador_notificacoes.php
// Motor inteligente para geração de notificações de contas fixas

function verificarEGeraNotificacoes($pdo, $usuario_id) {
    date_default_timezone_set('America/Recife');
    $hoje = date('Y-m-d');
    $mesAtual = (int) date('m');
    $anoAtual = (int) date('Y');

    try {
        // 1. Busca ocorrências do mês atual para o usuário
        $stmt = $pdo->prepare("
            SELECT p.*, d.nome as nome_conta 
            FROM pagamentos_fixas p
            JOIN despesas_fixas d ON p.despesa_fixa_id = d.id
            WHERE p.usuario_id = :usuario_id 
              AND p.mes_referencia = :mes 
              AND p.ano_referencia = :ano
        ");
        $stmt->execute([':usuario_id' => $usuario_id, ':mes' => $mesAtual, ':ano' => $anoAtual]);
        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pagamentos as $pg) {
            $vencimento = $pg['data_vencimento'];
            $status = $pg['status'];
            $pagamento_id = $pg['id'];
            $nomeConta = htmlspecialchars($pg['nome_conta']);
            $valorFmt = 'R$ ' . number_format($pg['valor'], 2, ',', '.');

            // Se a conta já estiver paga, ignoramos avisos de pendência/atraso
            if ($status === 'paga') {
                continue;
            }

            // Cálculo da diferença de dias entre hoje e o vencimento
            $diffDias = (strtotime($vencimento) - strtotime($hoje)) / (60 * 60 * 24);
            $diffDias = round($diffDias); // 5, 2, 0, negativo se atrasado

            $tipoNotificacao = '';
            $titulo = '';
            $mensagem = '';

            if ($diffDias === 5) {
                $tipoNotificacao = 'vencimento_5d';
                $titulo = 'Conta próxima do vencimento';
                $mensagem = "A conta '{$nomeConta}' ({$valorFmt}) vence daqui a 5 dias ({$vencimento}).";
            } elseif ($diffDias === 2) {
                $tipoNotificacao = 'vencimento_2d';
                $titulo = 'Atenção ao vencimento';
                $mensagem = "A conta '{$nomeConta}' ({$valorFmt}) vence em breve (em 2 dias).";
            } elseif ($diffDias === 0) {
                $tipoNotificacao = 'vencimento_hoje';
                $titulo = 'Conta vence hoje!';
                $mensagem = "A conta '{$nomeConta}' ({$valorFmt}) vence hoje e ainda está pendente.";
            } elseif ($diffDias < 0) {
                $diasAtraso = abs($diffDias);
                $tipoNotificacao = 'atrasada';
                $titulo = 'Conta em atraso';
                $mensagem = "A conta '{$nomeConta}' ({$valorFmt}) está atrasada há {$diasAtraso} dia(s).";
            }

            // Se identificamos uma regra de aviso aplicável, verificamos a duplicidade
            if (!empty($tipoNotificacao)) {
                // Verifica se já foi gerada notificação desse tipo para esta ocorrência no dia de hoje
                $check = $pdo->prepare("
                    SELECT id FROM notificacoes 
                    WHERE usuario_id = :uid 
                      AND pagamento_fixo_id = :pid 
                      AND tipo = :tipo 
                      AND DATE(created_at) = :hoje
                ");
                $check->execute([
                    ':uid'  => $usuario_id,
                    ':pid'  => $pagamento_id,
                    ':tipo' => $tipoNotificacao,
                    ':hoje' => $hoje
                ]);

                if (!$check->fetch()) {
                    // Insere a notificação de forma segura
                    $insert = $pdo->prepare("
                        INSERT INTO notificacoes (usuario_id, pagamento_fixo_id, tipo, titulo, mensagem, lida, created_at)
                        VALUES (:uid, :pid, :tipo, :tit, :msg, FALSE, CURRENT_TIMESTAMP)
                    ");
                    $insert->execute([
                        ':uid'  => $usuario_id,
                        ':pid'  => $pagamento_id,
                        ':tipo' => $tipoNotificacao,
                        ':tit'  => $titulo,
                        ':msg'  => $mensagem
                    ]);
                }
            }
        }
    } catch (PDOException $e) {
        // Tratamento de erro silencioso para não quebrar a página principal
        error_log("Erro no gerador de notificações: " . $e->getMessage());
    }
}