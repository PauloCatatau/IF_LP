USE `tarefinhahugofofo`;

INSERT INTO `cad_user` (`emailnumero`, `senha`)
VALUES
    ('ana.silva@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('bruno.santos@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('carla.oliveira@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('diego.costa@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('elaine.rocha@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('felipe.alves@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('gabriela.lima@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu'),
    ('henrique.martins@exemplo.com', '$2y$10$ofmgEQZEXSmguDdyjt0CFOzJ.9RUfjMr6cKebW2Mb4iW/ualdvTPu')
ON DUPLICATE KEY UPDATE `emailnumero` = VALUES(`emailnumero`);

INSERT INTO `tarefas` (`usuario_id`, `titulo`, `descricao`, `prioridade`, `status`, `created_at`)
VALUES
    (1, 'Planejar sprint de produto', 'Definir metas e entregas da próxima sprint.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 32 DAY)),
    (1, 'Revisar requisitos do aplicativo', 'Conferir requisitos com o time de negócio.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 28 DAY)),
    (1, 'Atualizar mapa de riscos', 'Registrar riscos técnicos e planos de mitigação.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 21 DAY)),
    (1, 'Validar protótipo com usuários', 'Realizar cinco entrevistas de validação.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 12 DAY)),
    (1, 'Organizar documentação do produto', 'Centralizar documentos no repositório do projeto.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (1, 'Preparar apresentação executiva', 'Montar indicadores para a reunião mensal.', 'media', 'pendente', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 'Implementar autenticação', 'Adicionar login seguro e controle de sessão.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 40 DAY)),
    (2, 'Criar testes do cadastro', 'Cobrir cenários válidos e inválidos.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 35 DAY)),
    (2, 'Corrigir falha no formulário', 'Resolver validação de campos obrigatórios.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 25 DAY)),
    (2, 'Otimizar consulta de tarefas', 'Reduzir tempo de resposta da listagem.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 18 DAY)),
    (2, 'Atualizar dependências PHP', 'Verificar compatibilidade e vulnerabilidades.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 9 DAY)),
    (2, 'Configurar ambiente de homologação', 'Documentar variáveis e serviços necessários.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (3, 'Criar identidade visual', 'Definir cores, tipografia e componentes.', 'media', 'concluida', DATE_SUB(NOW(), INTERVAL 45 DAY)),
    (3, 'Desenhar tela inicial', 'Produzir layout responsivo para dashboard.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 31 DAY)),
    (3, 'Revisar acessibilidade', 'Avaliar contraste, navegação e textos alternativos.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 22 DAY)),
    (3, 'Preparar ícones do sistema', 'Selecionar conjunto consistente de ícones.', 'baixa', 'em andamento', DATE_SUB(NOW(), INTERVAL 15 DAY)),
    (3, 'Testar fluxo em celular', 'Validar telas em diferentes larguras.', 'media', 'pendente', DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (3, 'Documentar componentes', 'Registrar estados e regras de uso.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (4, 'Mapear processo financeiro', 'Documentar entradas, aprovações e saídas.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 50 DAY)),
    (4, 'Conferir notas fiscais', 'Revisar documentos recebidos no mês.', 'media', 'concluida', DATE_SUB(NOW(), INTERVAL 29 DAY)),
    (4, 'Atualizar planilha de custos', 'Incluir despesas recorrentes do trimestre.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 20 DAY)),
    (4, 'Solicitar orçamento de fornecedor', 'Comparar três propostas comerciais.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 11 DAY)),
    (4, 'Arquivar comprovantes', 'Organizar comprovantes por centro de custo.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 5 DAY)),
    (4, 'Fechar relatório mensal', 'Consolidar receitas e despesas.', 'alta', 'pendente', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (5, 'Planejar campanha de conteúdo', 'Definir canais, temas e calendário editorial.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 38 DAY)),
    (5, 'Revisar calendário editorial', 'Ajustar datas e responsáveis pelas publicações.', 'media', 'concluida', DATE_SUB(NOW(), INTERVAL 27 DAY)),
    (5, 'Produzir newsletter', 'Escrever edição com novidades do produto.', 'media', 'concluida', DATE_SUB(NOW(), INTERVAL 19 DAY)),
    (5, 'Analisar métricas de campanha', 'Comparar alcance, cliques e conversões.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 14 DAY)),
    (5, 'Criar briefing de vídeo', 'Detalhar roteiro e referências visuais.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (5, 'Agendar publicações', 'Programar conteúdos aprovados da semana.', 'media', 'pendente', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (6, 'Levantar necessidades do cliente', 'Conduzir reunião de descoberta.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 42 DAY)),
    (6, 'Atualizar propostas comerciais', 'Revisar escopo, prazo e valores.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 33 DAY)),
    (6, 'Realizar demonstração', 'Apresentar solução para o cliente potencial.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 16 DAY)),
    (6, 'Registrar oportunidades no CRM', 'Cadastrar contatos e próximos passos.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (6, 'Enviar pesquisa de satisfação', 'Disparar pesquisa para clientes ativos.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 7 DAY)),
    (6, 'Planejar follow-up semanal', 'Definir agenda de retornos comerciais.', 'media', 'pendente', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (7, 'Configurar pipeline de deploy', 'Automatizar validação e publicação.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 55 DAY)),
    (7, 'Monitorar disponibilidade do servidor', 'Revisar alertas e indicadores de uptime.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 26 DAY)),
    (7, 'Atualizar documentação técnica', 'Registrar arquitetura e procedimentos operacionais.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 17 DAY)),
    (7, 'Revisar permissões de acesso', 'Remover acessos antigos e validar perfis.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 13 DAY)),
    (7, 'Testar rotina de backup', 'Executar restauração em ambiente de teste.', 'alta', 'pendente', DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (7, 'Catalogar ativos de tecnologia', 'Atualizar inventário de equipamentos.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (8, 'Planejar treinamento interno', 'Definir agenda e materiais do treinamento.', 'media', 'concluida', DATE_SUB(NOW(), INTERVAL 47 DAY)),
    (8, 'Atualizar manual de integração', 'Revisar orientações para novas pessoas.', 'baixa', 'concluida', DATE_SUB(NOW(), INTERVAL 36 DAY)),
    (8, 'Agendar entrevistas', 'Organizar horários com candidatos.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 24 DAY)),
    (8, 'Consolidar avaliação de desempenho', 'Preparar síntese do ciclo atual.', 'alta', 'em andamento', DATE_SUB(NOW(), INTERVAL 12 DAY)),
    (8, 'Revisar política de férias', 'Conferir regras e comunicação interna.', 'media', 'em andamento', DATE_SUB(NOW(), INTERVAL 5 DAY)),
    (8, 'Organizar pesquisa interna', 'Preparar formulário de clima organizacional.', 'baixa', 'pendente', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (1, 'Revisar backlog priorizado', 'Ordenar itens conforme impacto e esforço.', 'alta', 'concluida', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (2, 'Publicar notas da versão', 'Escrever resumo das correções entregues.', 'baixa', 'concluida', DATE_SUB(NOW(), INTERVAL 4 DAY));

UPDATE tarefas
SET prazo = CASE
    WHEN status = 'concluida' THEN DATE_ADD(DATE(created_at), INTERVAL 7 DAY)
    WHEN id % 3 = 0 THEN DATE_SUB(CURDATE(), INTERVAL 2 DAY)
    ELSE DATE_ADD(CURDATE(), INTERVAL (id % 14) DAY)
END
WHERE prazo IS NULL;
