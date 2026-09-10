<?php
session_start();

if (!isset($_SESSION['usuario'], $_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

include "conexao.php";

$usuario = $_SESSION['usuario'];
$usuario_id = (int)$_SESSION['id'];
$admins = ['catataubocamole767@gmail.com', 'paulocatatau5@gmail.com'];
$eh_admin = in_array(strtolower($usuario), $admins, true);
$visao = isset($_GET['visao']) && $_GET['visao'] === 'admin' && $eh_admin ? 'admin' : 'pessoal';
$analise = trim($_GET['analise'] ?? 'geral');
$analises_validas = ['geral' => true, 'evolucao' => true, 'distribuicao' => true, 'situacao' => true, 'prioridades' => true];
if (!array_key_exists($analise, $analises_validas)) $analise = 'geral';

$consultar = function ($sql, $tipos, $valores) use ($conexao) {
    $stmt = $conexao->prepare($sql);
    if ($tipos !== '') $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
};

$status = [
    'pendente' => 0,
    'em andamento' => 0,
    'concluida' => 0
];
$prioridades = [
    'baixa' => 0,
    'media' => 0,
    'alta' => 0
];
$por_mes = [];

$resultado = $consultar("SELECT t.status, COUNT(*) AS total FROM tarefas t WHERE t.usuario_id = ? GROUP BY t.status", 'i', [$usuario_id]);
while ($linha = $resultado->fetch_assoc()) {
    if (array_key_exists($linha['status'], $status)) {
        $status[$linha['status']] = (int)$linha['total'];
    }
}

$resultado = $consultar("SELECT t.prioridade, COUNT(*) AS total FROM tarefas t WHERE t.usuario_id = ? GROUP BY t.prioridade", 'i', [$usuario_id]);
while ($linha = $resultado->fetch_assoc()) {
    if (array_key_exists($linha['prioridade'], $prioridades)) {
        $prioridades[$linha['prioridade']] = (int)$linha['total'];
    }
}

$resultado = $consultar("SELECT DATE_FORMAT(t.created_at, '%Y-%m') AS mes, COUNT(*) AS total FROM tarefas t WHERE t.usuario_id = ? GROUP BY mes ORDER BY mes", 'i', [$usuario_id]);
while ($linha = $resultado->fetch_assoc()) {
    $por_mes[$linha['mes']] = (int)$linha['total'];
}

$meses = [];
$totais_mensais = [];
foreach ($por_mes as $mes => $total) {
    $data_mes = DateTime::createFromFormat('!Y-m', $mes);
    $meses[] = $data_mes ? $data_mes->format('m/Y') : $mes;
    $totais_mensais[] = $total;
}

$total_tarefas = array_sum($status);
$status_grafico = $status;
$prioridades_grafico = $prioridades;

$admin_status = [
    'pendente' => 0,
    'em andamento' => 0,
    'concluida' => 0
];
$admin_por_usuario = [];
$admin_por_mes = [];

if ($visao === 'admin') {
    $resultado = $conexao->query("SELECT t.status, COUNT(*) AS total FROM tarefas t GROUP BY t.status");
    while ($linha = $resultado->fetch_assoc()) {
        if (array_key_exists($linha['status'], $admin_status)) {
            $admin_status[$linha['status']] = (int)$linha['total'];
        }
    }

    $resultado = $conexao->query("SELECT u.emailnumero, COUNT(t.id) AS total
        FROM cad_user u INNER JOIN tarefas t ON t.usuario_id = u.id
        GROUP BY u.id, u.emailnumero ORDER BY total DESC, u.emailnumero");
    while ($linha = $resultado->fetch_assoc()) {
        $admin_por_usuario[] = [
            'usuario' => $linha['emailnumero'],
            'total' => (int)$linha['total']
        ];
    }

    $resultado = $conexao->query("SELECT DATE_FORMAT(t.created_at, '%Y-%m') AS mes, COUNT(*) AS total
        FROM tarefas t GROUP BY mes ORDER BY mes");
    while ($linha = $resultado->fetch_assoc()) {
        $admin_por_mes[$linha['mes']] = (int)$linha['total'];
    }
}

$rotulos_status = ['pendente' => 'Pendentes', 'em andamento' => 'Em andamento', 'concluida' => 'Concluídas'];
$rotulos_prioridade = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
$cores_status = ['pendente' => '#ffd166', 'em andamento' => '#66ccff', 'concluida' => '#7fff00'];

$contexto_filtros = 'todos os registros';

$admin_meses = [];
$admin_totais_mensais = [];
foreach ($admin_por_mes as $mes => $total) {
    $data_mes = DateTime::createFromFormat('!Y-m', $mes);
    $admin_meses[] = $data_mes ? $data_mes->format('m/Y') : $mes;
    $admin_totais_mensais[] = $total;
}
$admin_total_tarefas = array_sum($admin_status);
$admin_status_grafico = $admin_status;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard <?= $visao === 'admin' ? 'administrativo' : 'pessoal' ?> | Cuiudo System</title>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        color: #efffdc;
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, rgba(10, 25, 5, .96), rgba(25, 45, 12, .9)), url('https://media1.tenor.com/m/qK9AVPQCWbgAAAAd/woody-woodpecker-wolfie-wolf.gif') center/cover fixed;
    }
    header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px clamp(18px, 5vw, 70px);
        border-bottom: 1px solid rgba(173,255,47,.35);
        background: rgba(0,0,0,.42);
    }
    h1 { margin: 0 0 5px; font-size: clamp(24px, 4vw, 38px); }
    .subtitulo { margin: 0; color: #c9eba9; }
    .voltar {
        color: #14220b;
        background: #adff2f;
        border-radius: 6px;
        padding: 10px 15px;
        font-weight: bold;
        text-decoration: none;
        white-space: nowrap;
    }
    .visoes { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .visao-link { color: #efffdc; border: 1px solid rgba(173,255,47,.45); border-radius: 6px; padding: 8px 12px; text-decoration: none; }
    .visao-link.ativa, .visao-link:hover { color: #14220b; background: #adff2f; }
    main { max-width: 1200px; margin: 0 auto; padding: 28px clamp(18px, 5vw, 70px) 45px; }
    .indicadores { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    .indicador, .grafico-box {
        background: rgba(0,0,0,.58);
        border: 1px solid rgba(173,255,47,.32);
        border-radius: 8px;
    }
    .indicador { padding: 16px 18px; }
    .indicador small { display: block; color: #c9eba9; }
    .indicador strong { display: block; margin-top: 5px; font-size: 28px; color: #adff2f; }
    .filtros { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; background: rgba(0,0,0,.58); border: 1px solid rgba(173,255,47,.32); border-radius: 8px; }
    .filtros label { display: flex; flex-direction: column; gap: 5px; color: #c9eba9; font-size: 12px; }
    .filtros input, .filtros select, .filtros button { min-height: 36px; padding: 7px 9px; border: 0; border-radius: 5px; font: inherit; }
    .filtros button { cursor: pointer; color: #14220b; background: #adff2f; font-weight: bold; }
    .filtros .limpar { color: #efffdc; background: transparent; border: 1px solid rgba(173,255,47,.45); text-decoration: none; display: inline-flex; align-items: center; }
    .filtro-resumo { width: 100%; margin: 0; color: #c9eba9; font-size: 12px; }
    .graficos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .grafico-box { min-height: 350px; padding: 16px; }
    .grafico-box h2 { margin: 0 0 12px; font-size: 17px; color: #efffdc; }
    .grafico-info { margin: -4px 0 10px; color: #c9eba9; font-size: 13px; line-height: 1.45; }
    .grafico-info strong { color: #efffdc; }
    .legenda { display: flex; gap: 12px; flex-wrap: wrap; margin: 0 0 5px; color: #efffdc; font-size: 12px; }
    .legenda-item { display: inline-flex; align-items: center; gap: 5px; }
    .cor { width: 11px; height: 11px; display: inline-block; border-radius: 2px; }
    .grafico { width: 100%; height: 285px; }
    .vazio { display: grid; place-items: center; height: 285px; color: #c9eba9; text-align: center; }
    @media (max-width: 700px) {
        header { align-items: flex-start; flex-direction: column; }
        .indicadores, .graficos { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>
<header>
    <div>
        <h1><?= $visao === 'admin' ? 'Dashboard administrativo' : 'Dashboard pessoal' ?></h1>
        <p class="subtitulo"><?= $visao === 'admin' ? 'Visão geral das tarefas do sistema' : 'Acompanhe suas tarefas' ?>, <?= htmlspecialchars($usuario) ?></p>
        <?php if ($eh_admin): ?>
        <nav class="visoes">
            <a class="visao-link <?= $visao === 'pessoal' ? 'ativa' : '' ?>" href="dashboard.php">Minha visão</a>
            <a class="visao-link <?= $visao === 'admin' ? 'ativa' : '' ?>" href="dashboard.php?visao=admin">Visão administrativa</a>
        </nav>
        <?php endif; ?>
    </div>
    <a class="voltar" href="telainicial.php">Voltar para tarefas</a>
</header>

<main>
    <form class="filtros" method="get">
        <input type="hidden" name="visao" value="<?= $visao ?>">
        <label>Análise pronta
            <select name="analise">
                <option value="geral" <?= $analise === 'geral' ? 'selected' : '' ?>>Todos</option>
                <option value="evolucao" <?= $analise === 'evolucao' ? 'selected' : '' ?>>Evolução no tempo</option>
                <option value="distribuicao" <?= $analise === 'distribuicao' ? 'selected' : '' ?>>Distribuição do trabalho</option>
                <option value="situacao" <?= $analise === 'situacao' ? 'selected' : '' ?>>Situação por status</option>
                <?php if ($visao === 'pessoal'): ?><option value="prioridades" <?= $analise === 'prioridades' ? 'selected' : '' ?>>Tarefas que precisam de atenção</option><?php endif; ?>
            </select>
        </label>
        <button type="submit">Abrir análise</button>
        <p class="filtro-resumo">Escolha uma análise pronta para visualizar somente o gráfico correspondente.</p>
    </form>

    <section class="indicadores">
        <?php if ($visao === 'admin'): ?>
        <div class="indicador"><small>Total de tarefas</small><strong><?= $admin_total_tarefas ?></strong></div>
        <div class="indicador"><small>Usuários com tarefas</small><strong><?= count(array_filter($admin_por_usuario, fn($usuario) => $usuario['total'] > 0)) ?></strong></div>
        <div class="indicador"><small>Taxa de conclusão</small><strong><?= $admin_total_tarefas ? round($admin_status['concluida'] * 100 / $admin_total_tarefas, 1) : 0 ?>%</strong></div>
        <?php else: ?>
        <div class="indicador"><small>Total de tarefas</small><strong><?= $total_tarefas ?></strong></div>
        <div class="indicador"><small>Concluídas</small><strong><?= $status['concluida'] ?></strong></div>
        <div class="indicador"><small>Em aberto</small><strong><?= $status['pendente'] + $status['em andamento'] ?></strong></div>
        <?php endif; ?>
    </section>

    <section class="graficos">
        <?php if ($visao === 'admin'): ?>
        <?php if ($analise === 'geral' || $analise === 'evolucao'): ?>
        <article class="grafico-box">
            <h2>Quando as tarefas são criadas?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> tarefas criadas por mês para identificar períodos de maior movimento.</p>
            <?php if ($admin_por_mes): ?><div id="graficoLinha" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php if ($analise === 'geral' || $analise === 'distribuicao'): ?>
        <article class="grafico-box">
            <h2>Quem concentra mais tarefas?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> tarefas por usuário para comparar a distribuição do trabalho.</p>
            <div class="legenda"><span class="legenda-item"><span class="cor" style="background:#66ccff"></span>Tarefas cadastradas</span></div>
            <?php if ($admin_total_tarefas): ?><div id="graficoBarras" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php if ($analise === 'geral' || $analise === 'situacao'): ?>
        <article class="grafico-box">
            <h2>Qual é a situação das tarefas?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> a divisão entre pendentes, em andamento e concluídas.</p>
            <?php if ($admin_total_tarefas): ?><div id="graficoPizza" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php else: ?>
        <?php if ($analise === 'geral' || $analise === 'evolucao'): ?>
        <article class="grafico-box">
            <h2>Como está meu ritmo de criação?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> suas tarefas criadas por mês para perceber seu ritmo de organização.</p>
            <?php if ($por_mes): ?><div id="graficoLinha" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php if ($analise === 'geral' || $analise === 'prioridades'): ?>
        <article class="grafico-box">
            <h2>O que precisa de mais atenção?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> a quantidade em cada prioridade para localizar tarefas urgentes.</p>
            <div class="legenda">
                <span class="legenda-item"><span class="cor" style="background:#ff6666"></span>Alta</span>
                <span class="legenda-item"><span class="cor" style="background:#ffa500"></span>Média</span>
                <span class="legenda-item"><span class="cor" style="background:#88ee88"></span>Baixa</span>
            </div>
            <?php if ($total_tarefas): ?><div id="graficoBarras" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php if ($analise === 'geral' || $analise === 'situacao'): ?>
        <article class="grafico-box">
            <h2>Quanto já foi concluído?</h2>
            <p class="grafico-info"><strong>Filtro aplicado:</strong> <?= htmlspecialchars($contexto_filtros) ?>. <strong>Mostra:</strong> a proporção das suas tarefas por status para acompanhar o avanço.</p>
            <?php if ($total_tarefas): ?><div id="graficoPizza" class="grafico"></div><?php else: ?><div class="vazio">Ainda não há tarefas para exibir.</div><?php endif; ?>
        </article>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<script>
    google.charts.load('current', { packages: ['corechart'] });
    google.charts.setOnLoadCallback(desenharGraficos);

    function desenharGraficos() {
        const opcoesBase = {
            backgroundColor: 'transparent',
            fontName: 'Arial',
            legend: { textStyle: { color: '#efffdc' } },
            chartArea: { left: 55, top: 15, width: '82%', height: '72%' }
        };

        <?php if ($visao === 'admin'): ?>
        <?php if ($admin_por_mes): ?>
        var dadosLinha = google.visualization.arrayToDataTable([
            ['Mês', 'Tarefas criadas'],
            <?php foreach ($admin_meses as $indice => $mes): ?>
            [<?= json_encode($mes) ?>, <?= $admin_totais_mensais[$indice] ?>]<?= $indice < count($admin_meses) - 1 ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoLinha')) new google.visualization.LineChart(document.getElementById('graficoLinha')).draw(dadosLinha, {
            ...opcoesBase,
            title: 'Tarefas criadas por mês',
            colors: ['#adff2f'],
            curveType: 'function',
            pointSize: 6,
            hAxis: { title: 'Mês de criação', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' } },
            vAxis: { title: 'Quantidade de tarefas', minValue: 0, format: '0', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' }, gridlines: { color: 'rgba(173,255,47,.18)' } }
        });
        <?php endif; ?>

        <?php if ($admin_total_tarefas): ?>
        var dadosBarras = google.visualization.arrayToDataTable([
            ['Usuário', 'Tarefas'],
            <?php foreach ($admin_por_usuario as $indice => $usuario_admin): ?>
            [<?= json_encode($usuario_admin['usuario'], JSON_UNESCAPED_UNICODE) ?>, <?= $usuario_admin['total'] ?>]<?= $indice < count($admin_por_usuario) - 1 ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoBarras')) new google.visualization.ColumnChart(document.getElementById('graficoBarras')).draw(dadosBarras, {
            ...opcoesBase,
            title: 'Quantidade de tarefas por usuário',
            colors: ['#66ccff'],
            hAxis: { title: 'Usuário', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' } },
            vAxis: { title: 'Quantidade de tarefas', minValue: 0, format: '0', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' }, gridlines: { color: 'rgba(173,255,47,.18)' } }
        });

        var dadosPizza = google.visualization.arrayToDataTable([
            ['Status', 'Quantidade'],
            <?php foreach ($admin_status_grafico as $status_chave => $status_total): ?>
            [<?= json_encode($rotulos_status[$status_chave] ?? ucfirst($status_chave), JSON_UNESCAPED_UNICODE) ?>, <?= $status_total ?>]<?= $status_chave !== array_key_last($admin_status_grafico) ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoPizza')) new google.visualization.PieChart(document.getElementById('graficoPizza')).draw(dadosPizza, {
            ...opcoesBase,
            title: 'Distribuição por status',
            legend: { position: 'right', textStyle: { color: '#efffdc' } },
            pieHole: 0.18,
            pieSliceText: 'value',
            colors: [<?php foreach ($admin_status_grafico as $status_chave => $status_total): ?><?= json_encode($cores_status[$status_chave]) ?><?= $status_chave !== array_key_last($admin_status_grafico) ? ',' : '' ?><?php endforeach; ?>]
        });
        <?php endif; ?>
        <?php else: ?>
        <?php if ($por_mes): ?>
        var dadosLinha = google.visualization.arrayToDataTable([
            ['Mês', 'Tarefas criadas'],
            <?php foreach ($meses as $indice => $mes): ?>
            [<?= json_encode($mes) ?>, <?= $totais_mensais[$indice] ?>]<?= $indice < count($meses) - 1 ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoLinha')) new google.visualization.LineChart(document.getElementById('graficoLinha')).draw(dadosLinha, {
            ...opcoesBase,
            title: 'Suas tarefas criadas por mês',
            colors: ['#adff2f'],
            curveType: 'function',
            pointSize: 6,
            hAxis: { title: 'Mês de criação', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' } },
            vAxis: { title: 'Quantidade de tarefas', minValue: 0, format: '0', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' }, gridlines: { color: 'rgba(173,255,47,.18)' } }
        });
        <?php endif; ?>

        <?php if ($total_tarefas): ?>
        var dadosBarras = google.visualization.arrayToDataTable([
            ['Prioridade', 'Quantidade', { role: 'style' }, { role: 'annotation' }],
            <?php $cores_prioridade = ['alta' => '#ff6666', 'media' => '#ffa500', 'baixa' => '#88ee88']; ?>
            <?php foreach ($prioridades_grafico as $prioridade_chave => $prioridade_total): ?>
            [<?= json_encode($rotulos_prioridade[$prioridade_chave] ?? ucfirst($prioridade_chave), JSON_UNESCAPED_UNICODE) ?>, <?= $prioridade_total ?>, <?= json_encode($cores_prioridade[$prioridade_chave]) ?>, '<?= $prioridade_total ?>']<?= $prioridade_chave !== array_key_last($prioridades_grafico) ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoBarras')) new google.visualization.ColumnChart(document.getElementById('graficoBarras')).draw(dadosBarras, {
            ...opcoesBase,
            title: 'Tarefas por nível de prioridade',
            legend: { position: 'none' },
            annotations: { textStyle: { color: '#efffdc', fontSize: 13 } },
            hAxis: { title: 'Prioridade', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' } },
            vAxis: { title: 'Quantidade de tarefas', minValue: 0, format: '0', textStyle: { color: '#c9eba9' }, titleTextStyle: { color: '#c9eba9' }, gridlines: { color: 'rgba(173,255,47,.18)' } }
        });

        var dadosPizza = google.visualization.arrayToDataTable([
            ['Status', 'Quantidade'],
            <?php foreach ($status_grafico as $status_chave => $status_total): ?>
            [<?= json_encode($rotulos_status[$status_chave] ?? ucfirst($status_chave), JSON_UNESCAPED_UNICODE) ?>, <?= $status_total ?>]<?= $status_chave !== array_key_last($status_grafico) ? ',' : '' ?>
            <?php endforeach; ?>
        ]);
        if (document.getElementById('graficoPizza')) new google.visualization.PieChart(document.getElementById('graficoPizza')).draw(dadosPizza, {
            ...opcoesBase,
            title: 'Distribuição das suas tarefas por status',
            legend: { position: 'right', textStyle: { color: '#efffdc' } },
            pieHole: 0.18,
            pieSliceText: 'value',
            colors: [<?php foreach ($status_grafico as $status_chave => $status_total): ?><?= json_encode($cores_status[$status_chave]) ?><?= $status_chave !== array_key_last($status_grafico) ? ',' : '' ?><?php endforeach; ?>]
        });
        <?php endif; ?>
        <?php endif; ?>
    }
</script>
</body>
</html>
