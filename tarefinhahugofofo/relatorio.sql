USE `tarefinhahugofofo`;

-- 1. Painel executivo geral
SELECT
    COUNT(*) AS total_atividades,
    COUNT(DISTINCT usuario_id) AS usuarios_com_atividade,
    SUM(status = 'concluida') AS concluidas,
    SUM(status = 'em andamento') AS em_andamento,
    SUM(status = 'pendente') AS pendentes,
    ROUND(SUM(status = 'concluida') * 100.0 / NULLIF(COUNT(*), 0), 2) AS taxa_conclusao_pct,
    ROUND(AVG(TIMESTAMPDIFF(DAY, created_at, NOW())), 1) AS idade_media_dias,
    SUM(prioridade = 'alta' AND status <> 'concluida') AS altas_em_aberto,
    SUM(status <> 'concluida' AND prazo IS NOT NULL AND prazo < CURDATE()) AS atrasadas
FROM tarefas;

-- 2. Desempenho por usuário, com taxa de conclusão e carga em aberto
SELECT
    u.id AS usuario_id,
    u.emailnumero AS usuario,
    COUNT(t.id) AS total_atividades,
    SUM(t.status = 'concluida') AS concluidas,
    SUM(t.status = 'em andamento') AS em_andamento,
    SUM(t.status = 'pendente') AS pendentes,
    SUM(t.prioridade = 'alta' AND t.status <> 'concluida') AS altas_em_aberto,
    SUM(t.status <> 'concluida' AND t.prazo IS NOT NULL AND t.prazo < CURDATE()) AS atrasadas,
    ROUND(SUM(t.status = 'concluida') * 100.0 / NULLIF(COUNT(t.id), 0), 2) AS taxa_conclusao_pct,
    ROUND(AVG(TIMESTAMPDIFF(DAY, t.created_at, NOW())), 1) AS idade_media_dias,
    MAX(t.created_at) AS ultima_atividade
FROM cad_user u
LEFT JOIN tarefas t ON t.usuario_id = u.id
GROUP BY u.id, u.emailnumero
ORDER BY taxa_conclusao_pct DESC, altas_em_aberto ASC, total_atividades DESC;

-- 3. Matriz de prioridade por status
SELECT
    prioridade,
    COUNT(*) AS total,
    SUM(status = 'pendente') AS pendentes,
    SUM(status = 'em andamento') AS em_andamento,
    SUM(status = 'concluida') AS concluidas,
    ROUND(SUM(status = 'concluida') * 100.0 / COUNT(*), 2) AS conclusao_pct
FROM tarefas
GROUP BY prioridade
ORDER BY FIELD(prioridade, 'alta', 'media', 'baixa');

-- 4. Distribuição de atividades por faixa de idade
SELECT
    CASE
        WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) <= 7 THEN '0-7 dias'
        WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) <= 30 THEN '8-30 dias'
        ELSE '31+ dias'
    END AS faixa_idade,
    COUNT(*) AS total,
    SUM(status = 'concluida') AS concluidas,
    SUM(status <> 'concluida') AS em_aberto,
    ROUND(AVG(TIMESTAMPDIFF(DAY, created_at, NOW())), 1) AS idade_media_dias
FROM tarefas
GROUP BY faixa_idade
ORDER BY MIN(TIMESTAMPDIFF(DAY, created_at, NOW()));

-- 5. Ranking de usuários por carga pendente ponderada
SELECT
    u.emailnumero AS usuario,
    SUM(CASE
        WHEN t.status = 'pendente' AND t.prioridade = 'alta' THEN 3
        WHEN t.status = 'pendente' AND t.prioridade = 'media' THEN 2
        WHEN t.status = 'pendente' AND t.prioridade = 'baixa' THEN 1
        WHEN t.status = 'em andamento' AND t.prioridade = 'alta' THEN 2
        ELSE 0
    END) AS carga_ponderada,
    SUM(t.status = 'pendente') AS pendentes,
    SUM(t.status = 'em andamento') AS em_andamento,
    SUM(t.prioridade = 'alta' AND t.status <> 'concluida') AS prioridades_altas_abertas
FROM cad_user u
JOIN tarefas t ON t.usuario_id = u.id
GROUP BY u.id, u.emailnumero
ORDER BY carga_ponderada DESC, prioridades_altas_abertas DESC;

-- 6. Atividades abertas mais antigas e prioritárias
SELECT
    t.id,
    u.emailnumero AS usuario,
    t.titulo,
    t.prioridade,
    t.status,
    t.prazo,
    t.created_at,
    TIMESTAMPDIFF(DAY, t.created_at, NOW()) AS dias_em_aberto
FROM tarefas t
JOIN cad_user u ON u.id = t.usuario_id
WHERE t.status <> 'concluida'
ORDER BY FIELD(t.prioridade, 'alta', 'media', 'baixa'), t.created_at ASC
LIMIT 15;

-- 7. Histórico de e-mails de recuperação enviados
SELECT
    email,
    COUNT(*) AS tentativas_recuperacao,
    SUM(enviado = 1) AS recuperacoes_enviadas,
    MAX(created_at) AS ultimo_envio
FROM historico_recuperacao
GROUP BY email
ORDER BY tentativas_recuperacao DESC, email;
