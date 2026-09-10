<?php
session_start();
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

include "conexao.php";

$usuario    = $_SESSION['usuario'];
$usuario_id = $_SESSION['id'];
$msg        = "";
$erro       = "";
$admins     = ['catataubocamole767@gmail.com', 'paulocatatau5@gmail.com'];
$eh_admin   = in_array(strtolower($usuario), $admins, true);

// Página ativa via GET (cadastrar | editar | consultar), padrão = cadastrar
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'cadastrar';
if ($pagina === 'relatorio' && !$eh_admin) {
    $pagina = 'consultar';
    $erro = 'Acesso restrito aos administradores.';
}

// ── CADASTRAR TAREFA ──────────────────────────────────────────────────────────
if (isset($_POST['cadastrar_tarefa'])) {
    $titulo     = trim($_POST['titulo']);
    $descricao  = trim($_POST['descricao']);
    $prioridade = trim($_POST['prioridade']);
    $prazo      = trim($_POST['prazo'] ?? '');
    $prazo      = $prazo !== '' ? $prazo : null;

    if (empty($titulo)) {
        $erro = "Bota um título aí cuiudo!";
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO tarefas (usuario_id, titulo, descricao, prioridade, prazo) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issss", $usuario_id, $titulo, $descricao, $prioridade, $prazo);
        $stmt->execute() ? $msg = "Tarefa criada, cuiudo!" : $erro = "Erro: " . $conexao->error;
        $stmt->close();
    }
    $pagina = 'cadastrar';
}

// ── EDITAR TAREFA ─────────────────────────────────────────────────────────────
if (isset($_POST['salvar_edicao'])) {
    $id_tarefa  = (int)$_POST['id_tarefa'];
    $titulo     = trim($_POST['titulo']);
    $descricao  = trim($_POST['descricao']);
    $prioridade = trim($_POST['prioridade']);
    $status     = trim($_POST['status']);
    $prazo      = trim($_POST['prazo'] ?? '');
    $prazo      = $prazo !== '' ? $prazo : null;

    if (empty($titulo)) {
        $erro = "Título não pode ficar vazio, cuiudo!";
    } else {
        $stmt = $conexao->prepare(
            "UPDATE tarefas SET titulo=?, descricao=?, prioridade=?, status=?, prazo=? WHERE id=? AND usuario_id=?"
        );
        $stmt->bind_param("sssssii", $titulo, $descricao, $prioridade, $status, $prazo, $id_tarefa, $usuario_id);
        $stmt->execute() ? $msg = "Tarefa atualizada!" : $erro = "Erro: " . $conexao->error;
        $stmt->close();
    }
    $pagina = 'consultar';
}

// ── DELETAR TAREFA ────────────────────────────────────────────────────────────
if (isset($_GET['deletar'])) {
    $id_del = (int)$_GET['deletar'];
    $stmt = $conexao->prepare("DELETE FROM tarefas WHERE id=? AND usuario_id=?");
    $stmt->bind_param("ii", $id_del, $usuario_id);
    $stmt->execute();
    $stmt->close();
    header("Location: telainicial.php?pagina=consultar&msg=deletada");
    exit();
}
if (isset($_GET['msg']) && $_GET['msg'] === 'deletada') $msg = "Tarefa deletada!";

// ── BUSCA TAREFA PARA EDITAR ──────────────────────────────────────────────────
$tarefa_editar = null;
if (isset($_GET['editar'])) {
    $pagina    = 'editar';
    $id_edit   = (int)$_GET['editar'];
    $stmt = $conexao->prepare("SELECT * FROM tarefas WHERE id=? AND usuario_id=?");
    $stmt->bind_param("ii", $id_edit, $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $tarefa_editar = $res->fetch_assoc();
    $stmt->close();
}

// ── CONSULTA COM FILTROS ──────────────────────────────────────────────────────
$filtro_status    = isset($_GET['f_status'])    ? trim($_GET['f_status'])    : '';
$filtro_prioridade= isset($_GET['f_prioridade'])? trim($_GET['f_prioridade']): '';
$filtro_titulo    = isset($_GET['f_titulo'])    ? trim($_GET['f_titulo'])    : '';

$where  = "WHERE usuario_id = ?";
$params = [$usuario_id];
$types  = "i";

if ($filtro_status) {
    $where   .= " AND status = ?";
    $params[] = $filtro_status;
    $types   .= "s";
}
if ($filtro_prioridade) {
    $where   .= " AND prioridade = ?";
    $params[] = $filtro_prioridade;
    $types   .= "s";
}
if ($filtro_titulo) {
    $where   .= " AND titulo LIKE ?";
    $params[] = "%$filtro_titulo%";
    $types   .= "s";
}

$tarefas = [];
$stmt = $conexao->prepare("SELECT * FROM tarefas $where ORDER BY created_at DESC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $tarefas[] = $row;
$stmt->close();

// Contadores para sidebar
$total     = count($tarefas);
$pendentes = count(array_filter($tarefas, fn($t) => $t['status'] === 'pendente'));
$andamento = count(array_filter($tarefas, fn($t) => $t['status'] === 'em andamento'));
$concluidas= count(array_filter($tarefas, fn($t) => $t['status'] === 'concluida'));

$relatorio_status = [];
$relatorio_tarefas = [];
$total_recuperacoes = 0;
$recuperacoes_por_email = [];
if ($pagina === 'relatorio') {
    $res = $conexao->query("SELECT status, COUNT(*) AS total FROM tarefas GROUP BY status");
    while ($row = $res->fetch_assoc()) $relatorio_status[$row['status']] = (int)$row['total'];

    $res = $conexao->query("SELECT t.id, u.emailnumero, t.titulo, t.prioridade, t.status,
        t.prazo, t.created_at, TIMESTAMPDIFF(DAY, t.created_at, NOW()) AS dias
        FROM tarefas t INNER JOIN cad_user u ON u.id = t.usuario_id
        ORDER BY t.created_at DESC");
    while ($row = $res->fetch_assoc()) $relatorio_tarefas[] = $row;

    $res = $conexao->query("SELECT COUNT(*) AS total FROM historico_recuperacao");
    $total_recuperacoes = (int)$res->fetch_assoc()['total'];
    $res = $conexao->query("SELECT email, COUNT(*) AS total,
        SUM(enviado = 1) AS enviados, MAX(created_at) AS ultimo_envio
        FROM historico_recuperacao GROUP BY email ORDER BY total DESC, email");
    while ($row = $res->fetch_assoc()) $recuperacoes_por_email[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        background-image: url('https://media1.tenor.com/m/qK9AVPQCWbgAAAAd/woody-woodpecker-wolfie-wolf.gif');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        color: greenyellow;
        font-family: sans-serif;
        display: flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
        width: 210px;
        min-height: 100vh;
        background: rgba(0,0,0,0.75);
        display: flex;
        flex-direction: column;
        padding: 20px 0;
        flex-shrink: 0;
        border-right: 2px solid greenyellow;
    }

    .sidebar h2 {
        text-align: center;
        font-size: 14px;
        color: greenyellow;
        padding: 0 10px 15px;
        border-bottom: 1px solid greenyellow;
        margin-bottom: 10px;
    }

    .sidebar a {
        display: block;
        color: greenyellow;
        text-decoration: none;
        padding: 12px 20px;
        font-size: 15px;
        border-left: 4px solid transparent;
        transition: background 0.2s;
    }

    .sidebar a:hover,
    .sidebar a.ativo {
        background: rgba(173,255,47,0.15);
        border-left: 4px solid greenyellow;
    }

    .sidebar .resumo {
        margin: 15px 10px;
        background: rgba(173,255,47,0.1);
        border-radius: 8px;
        padding: 10px;
        font-size: 13px;
        line-height: 2;
        border: 1px solid rgba(173,255,47,0.3);
    }

    .sidebar .sair {
        margin-top: auto;
        padding: 10px;
    }

    .sidebar .sair a {
        background: rgba(255,50,50,0.2);
        border-radius: 6px;
        text-align: center;
        border-left: none;
        color: #ff6666;
    }

    .sidebar .sair a:hover {
        background: rgba(255,50,50,0.4);
        border-left: none;
    }

    /* ── CONTEÚDO ── */
    .conteudo {
        flex: 1;
        padding: 25px 30px;
        text-align: center;
    }

    .conteudo h1 {
        margin-bottom: 5px;
    }

    .conteudo p.sub {
        font-size: 13px;
        color: #aaffaa;
        margin-bottom: 20px;
    }

    .msg-ok  { color: lime;   font-weight: bold; margin-bottom: 15px; }
    .msg-err { color: red;    font-weight: bold; margin-bottom: 15px; }

    input, button, select, textarea {
        padding: 8px;
        border-radius: 5px;
        border: none;
        font-size: 14px;
    }

    button { cursor: pointer; }

    .form-box {
        display: inline-block;
        background: rgba(0,0,0,0.6);
        padding: 25px 35px;
        border-radius: 10px;
        text-align: left;
        border: 1px solid rgba(173,255,47,0.3);
    }

    .form-box label { display: block; margin-bottom: 4px; font-size: 14px; }

    /* ── FILTROS ── */
    .filtros {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
        background: rgba(0,0,0,0.6);
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid rgba(173,255,47,0.3);
    }

    .filtros label { font-size: 12px; display: block; margin-bottom: 3px; }

    /* ── TABELA ── */
    table {
        margin: 0 auto;
        border-collapse: collapse;
        width: 100%;
        background: rgba(0,0,0,0.55);
        font-size: 14px;
    }

    th, td {
        border: 1px solid greenyellow;
        padding: 8px 12px;
        color: greenyellow;
    }

    th { background: rgba(0,0,0,0.75); }

    tr:hover td { background: rgba(173,255,47,0.07); }

    a { color: greenyellow; }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-pendente     { background: rgba(255,200,0,0.25);  color: #ffd700; border: 1px solid #ffd700; }
    .badge-andamento    { background: rgba(0,150,255,0.25);  color: #66ccff; border: 1px solid #66ccff; }
    .badge-concluida    { background: rgba(0,255,100,0.2);   color: lime;    border: 1px solid lime; }
    .badge-alta         { background: rgba(255,80,80,0.25);  color: #ff6666; border: 1px solid #ff6666; }
    .badge-media        { background: rgba(255,165,0,0.25);  color: #ffa500; border: 1px solid #ffa500; }
    .badge-baixa        { background: rgba(100,200,100,0.2); color: #88ee88; border: 1px solid #88ee88; }
    .prazo-atrasado { color: #ff6666; font-weight: bold; }
    .prazo-ok { color: #aaffaa; }

    .relatorio-acoes { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; margin-bottom: 18px; }
    .relatorio-acoes button { background: greenyellow; color: #14220b; font-weight: bold; }
    .relatorio-acoes button:hover { background: white; }
    .indicadores { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .indicador { background: rgba(0,0,0,0.65); border: 1px solid rgba(173,255,47,0.35); border-radius: 8px; padding: 15px; text-align: left; }
    .indicador small { color: #aaffaa; display: block; font-size: 12px; }
    .indicador strong { display: block; font-size: 28px; margin-top: 5px; }
    .relatorio-tabela { overflow-x: auto; }
    .relatorio-tabela table { min-width: 760px; }
    @media (max-width: 800px) { .indicadores { grid-template-columns: 1fr 1fr; } .conteudo { padding: 20px 12px; } }
    @media (max-width: 520px) { .sidebar { width: 165px; } .indicadores { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<div class="sidebar">
    <h2>📋 CUIUDO SYSTEM</h2>

    <a href="?pagina=cadastrar" class="<?= $pagina==='cadastrar'?'ativo':'' ?>">➕ Cadastrar Tarefa</a>
    <a href="?pagina=consultar" class="<?= ($pagina==='consultar'||$pagina==='editar')?'ativo':'' ?>">🔍 Minhas tarefas</a>
    <a href="dashboard.php">📈 Dashboard</a>
    <?php if ($eh_admin): ?>
    <a href="?pagina=relatorio" class="<?= $pagina==='relatorio'?'ativo':'' ?>">📊 Relatório administrativo</a>
    <?php endif; ?>

    <div class="resumo">
        <strong>Resumo:</strong><br>
        📌 Total: <?= $total ?><br>
        ⏳ Pendentes: <?= $pendentes ?><br>
        🔄 Andamento: <?= $andamento ?><br>
        ✅ Concluídas: <?= $concluidas ?>
    </div>

    <div class="sair">
        <a href="?logout=1">🚪 Sair</a>
    </div>
</div>

<!-- ═══════════════ CONTEÚDO ═══════════════ -->
<div class="conteudo">

    <h1>E AÍ CUIUDO, BORA FAZER ALGUMA COISA!</h1>
    <p class="sub">Logado como: <strong><?= htmlspecialchars($usuario) ?></strong></p>

    <?php if ($msg)  echo "<p class='msg-ok'>$msg</p>"; ?>
    <?php if ($erro) echo "<p class='msg-err'>$erro</p>"; ?>

    <?php if ($pagina === 'relatorio'): ?>
    <h2 style="margin-bottom:15px;">RELATÓRIO GERAL</h2>

    <div class="relatorio-acoes">
        <button type="button" onclick="exportarCSV()">📄 Exportar CSV</button>
        <button type="button" onclick="exportarPDF()">📕 Exportar PDF</button>
        <button type="button" onclick="window.print()">🖨️ Imprimir</button>
    </div>

    <div class="indicadores">
        <div class="indicador"><small>Total de atividades</small><strong><?= count($relatorio_tarefas) ?></strong></div>
        <div class="indicador"><small>Taxa de conclusão</small><strong><?= count($relatorio_tarefas) ? round(($relatorio_status['concluida'] ?? 0) * 100 / count($relatorio_tarefas), 1) : 0 ?>%</strong></div>
        <div class="indicador"><small>Em aberto</small><strong><?= ($relatorio_status['pendente'] ?? 0) + ($relatorio_status['em andamento'] ?? 0) ?></strong></div>
        <div class="indicador"><small>Alta prioridade aberta</small><strong><?= count(array_filter($relatorio_tarefas, fn($t) => $t['prioridade'] === 'alta' && $t['status'] !== 'concluida')) ?></strong></div>
        <div class="indicador"><small>Tentativas de recuperação</small><strong><?= $total_recuperacoes ?></strong></div>
    </div>

    <div class="relatorio-tabela" style="margin-bottom:20px;">
        <table>
            <tr><th>E-mail</th><th>Tentativas</th><th>Enviadas</th><th>Última tentativa</th></tr>
            <?php foreach ($recuperacoes_por_email as $recuperacao): ?>
            <tr>
                <td><?= htmlspecialchars($recuperacao['email']) ?></td>
                <td><?= (int)$recuperacao['total'] ?></td>
                <td><?= (int)$recuperacao['enviados'] ?></td>
                <td><?= htmlspecialchars($recuperacao['ultimo_envio']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$recuperacoes_por_email): ?><tr><td colspan="4">Nenhuma tentativa de recuperação registrada.</td></tr><?php endif; ?>
        </table>
    </div>

    <div class="relatorio-tabela">
        <table id="tabelaRelatorio">
            <tr><th>Usuário</th><th>Atividade</th><th>Prioridade</th><th>Status</th><th>Prazo</th><th>Criada em</th><th>Idade</th></tr>
            <?php foreach ($relatorio_tarefas as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['emailnumero']) ?></td>
                <td><?= htmlspecialchars($t['titulo']) ?></td>
                <td><?= htmlspecialchars(ucfirst($t['prioridade'])) ?></td>
                <td><?= htmlspecialchars(ucfirst($t['status'])) ?></td>
                <td class="<?= ($t['prazo'] && $t['status'] !== 'concluida' && $t['prazo'] < date('Y-m-d')) ? 'prazo-atrasado' : 'prazo-ok' ?>">
                    <?= $t['prazo'] ? date('d/m/Y', strtotime($t['prazo'])) : 'Sem prazo' ?>
                </td>
                <td><?= htmlspecialchars($t['created_at']) ?></td>
                <td><?= (int)$t['dias'] ?> dia(s)</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <script>
    function exportarCSV() {
        const linhas = [...document.querySelectorAll('#tabelaRelatorio tr')].map(linha => [...linha.cells].map(celula => '"' + celula.innerText.replaceAll('"', '""') + '"').join(';'));
        const blob = new Blob(['\ufeff' + linhas.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'relatorio-atividades.csv'; link.click(); URL.revokeObjectURL(link.href);
    }
    function exportarPDF() {
        const { jsPDF } = window.jspdf; const pdf = new jsPDF('landscape');
        pdf.setFontSize(16); pdf.text('Relatorio de atividades', 14, 15);
        pdf.setFontSize(10); pdf.text('Gerado em ' + new Date().toLocaleString('pt-BR'), 14, 22);
        pdf.autoTable({ html: '#tabelaRelatorio', startY: 28, styles: { fontSize: 7 }, headStyles: { fillColor: [30, 70, 20] } });
        pdf.save('relatorio-atividades.pdf');
    }
    </script>

    <?php elseif ($pagina === 'cadastrar'): ?>

    <h2 style="margin-bottom:15px;">CADASTRA UMA TAREFA AÍ</h2>

    <div class="form-box">
        <form method="post">
            <label>Título</label>
            <input name="titulo" size="30" type="text" required placeholder="O que tem que fazer cuiudo?">
            <br><br>

            <label>Descrição</label>
            <textarea name="descricao" cols="30" rows="3" placeholder="Detalhes (opcional)"></textarea>
            <br><br>

            <label>Prioridade</label>
            <select name="prioridade">
                <option value="baixa">Baixa</option>
                <option value="media" selected>Média</option>
                <option value="alta">Alta</option>
            </select>
            <br><br>

            <label>Data para concluir</label>
            <input name="prazo" type="date" min="<?= date('Y-m-d') ?>">
            <br><br>

            <button type="submit" name="cadastrar_tarefa">Cadastra!</button>
        </form>
    </div>

    <?php
    // ════════════════════════════════════════════════
    // PÁGINA: EDITAR
    // ════════════════════════════════════════════════
    elseif ($pagina === 'editar' && $tarefa_editar): ?>

    <h2 style="margin-bottom:15px;">EDITA AÍ CUIUDO</h2>

    <div class="form-box">
        <form method="post">
            <input type="hidden" name="id_tarefa" value="<?= $tarefa_editar['id'] ?>">

            <label>Título</label>
            <input name="titulo" size="30" type="text" required
                   value="<?= htmlspecialchars($tarefa_editar['titulo']) ?>">
            <br><br>

            <label>Descrição</label>
            <textarea name="descricao" cols="30" rows="3"><?= htmlspecialchars($tarefa_editar['descricao']) ?></textarea>
            <br><br>

            <label>Prioridade</label>
            <select name="prioridade">
                <option value="baixa"  <?= $tarefa_editar['prioridade']==='baixa' ?'selected':'' ?>>Baixa</option>
                <option value="media"  <?= $tarefa_editar['prioridade']==='media' ?'selected':'' ?>>Média</option>
                <option value="alta"   <?= $tarefa_editar['prioridade']==='alta'  ?'selected':'' ?>>Alta</option>
            </select>
            <br><br>

            <label>Status</label>
            <select name="status">
                <option value="pendente"     <?= $tarefa_editar['status']==='pendente'     ?'selected':'' ?>>Pendente</option>
                <option value="em andamento" <?= $tarefa_editar['status']==='em andamento' ?'selected':'' ?>>Em Andamento</option>
                <option value="concluida"    <?= $tarefa_editar['status']==='concluida'    ?'selected':'' ?>>Concluída</option>
            </select>
            <br><br>

            <label>Data para concluir</label>
            <input name="prazo" type="date" value="<?= htmlspecialchars($tarefa_editar['prazo'] ?? '') ?>">
            <br><br>

            <button type="submit" name="salvar_edicao">Salva aí!</button>
            &nbsp;
            <a href="?pagina=consultar"><button type="button">Cancela</button></a>
        </form>
    </div>

    <?php
    // ════════════════════════════════════════════════
    // PÁGINA: CONSULTAR
    // ════════════════════════════════════════════════
    else: ?>

    <h2 style="margin-bottom:15px;">SUAS TAREFAS</h2>

    <!-- Filtros -->
    <form method="get">
        <input type="hidden" name="pagina" value="consultar">
        <div class="filtros">
            <div>
                <label>Título</label>
                <input name="f_titulo" size="18" type="text"
                       value="<?= htmlspecialchars($filtro_titulo) ?>"
                       placeholder="Buscar...">
            </div>
            <div>
                <label>Status</label>
                <select name="f_status">
                    <option value="">Todos</option>
                    <option value="pendente"     <?= $filtro_status==='pendente'     ?'selected':'' ?>>Pendente</option>
                    <option value="em andamento" <?= $filtro_status==='em andamento' ?'selected':'' ?>>Em Andamento</option>
                    <option value="concluida"    <?= $filtro_status==='concluida'    ?'selected':'' ?>>Concluída</option>
                </select>
            </div>
            <div>
                <label>Prioridade</label>
                <select name="f_prioridade">
                    <option value="">Todas</option>
                    <option value="baixa" <?= $filtro_prioridade==='baixa'?'selected':'' ?>>Baixa</option>
                    <option value="media" <?= $filtro_prioridade==='media'?'selected':'' ?>>Média</option>
                    <option value="alta"  <?= $filtro_prioridade==='alta' ?'selected':'' ?>>Alta</option>
                </select>
            </div>
            <div>
                <button type="submit">🔍 Filtrar</button>
                &nbsp;
                <a href="?pagina=consultar"><button type="button">✖ Limpar</button></a>
            </div>
        </div>
    </form>

    <?php if (empty($tarefas)): ?>
        <p>Nenhuma tarefa encontrada. Tá de férias cuiudo?</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Título</th>
            <th>Descrição</th>
            <th>Prioridade</th>
            <th>Status</th>
            <th>Prazo</th>
            <th>Criado em</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($tarefas as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['titulo']) ?></td>
            <td><?= htmlspecialchars($t['descricao']) ?></td>
            <td>
                <span class="badge badge-<?= $t['prioridade'] ?>">
                    <?= ucfirst($t['prioridade']) ?>
                </span>
            </td>
            <td>
                <?php
                $cls = $t['status'] === 'pendente' ? 'pendente'
                     : ($t['status'] === 'em andamento' ? 'andamento' : 'concluida');
                ?>
                <span class="badge badge-<?= $cls ?>">
                    <?= ucfirst($t['status']) ?>
                </span>
            </td>
            <td class="<?= ($t['prazo'] && $t['status'] !== 'concluida' && $t['prazo'] < date('Y-m-d')) ? 'prazo-atrasado' : 'prazo-ok' ?>">
                <?= $t['prazo'] ? date('d/m/Y', strtotime($t['prazo'])) : 'Sem prazo' ?>
            </td>
            <td><?= $t['created_at'] ?></td>
            <td>
                <a href="?editar=<?= $t['id'] ?>">✏️</a>
                &nbsp;
                <a href="?deletar=<?= $t['id'] ?>&pagina=consultar"
                   onclick="return confirm('Apaga essa tarefa cuiudo?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p style="font-size:13px; margin-top:10px; color:#aaffaa;">
        Mostrando <?= count($tarefas) ?> tarefa(s)
    </p>
    <?php endif; ?>

    <?php endif; ?>

</div><!-- /conteudo -->

</body>
</html>
