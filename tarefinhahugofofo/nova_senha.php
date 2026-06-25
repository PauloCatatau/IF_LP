<?php
include "conexao.php";

$msg = "";
$erro = "";
$token_valido = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');

// Valida o token
if ($token) {
    $stmt = $conexao->prepare("SELECT email, expiracao FROM recuperacao_senha WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $linha = $result->fetch_assoc();
        if (strtotime($linha['expiracao']) > time()) {
            $token_valido = true;
            $email_recuperacao = $linha['email'];
        } else {
            $erro = "Esse link expirou, cuiudo! Pede um novo.";
        }
    } else {
        $erro = "Token inválido ou já usado. Pede um novo link.";
    }
    $stmt->close();
} else {
    $erro = "Nenhum token encontrado. Acessa pelo link do email!";
}

// Processa nova senha
if (isset($_POST['nova_senha']) && $token_valido) {
    $senha1 = trim($_POST['senha1']);
    $senha2 = trim($_POST['senha2']);

    if ($senha1 !== $senha2) {
        $erro = "As senhas não batem! Digita igual aí.";
        $token_valido = true;
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $senha1)) {
        $erro = "Senha fraca! Precisa: 8+ caracteres, maiúscula, minúscula, número e símbolo!";
        $token_valido = true;
    } else {
        $senha_hash = password_hash($senha1, PASSWORD_DEFAULT);

        $upd = $conexao->prepare("UPDATE cad_user SET senha = ? WHERE emailnumero = ?");
        $upd->bind_param("ss", $senha_hash, $email_recuperacao);
        $upd->execute();

        // Remove o token usado
        $del = $conexao->prepare("DELETE FROM recuperacao_senha WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();

        $msg = "Senha trocada com sucesso, cuiudo! Vai fazer login agora.";
        $token_valido = false;
    }
}
?>
<html>
<head>
<style>
    body {
        background-image: url('https://media1.tenor.com/m/LK628grSBnEAAAAC/cat-ai-pufferfish-cat.gif');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: auto;
        margin: 0;
        color: greenyellow;
    }

    input, button {
        padding: 8px;
        border-radius: 5px;
        border: none;
    }

    button { cursor: pointer; }
</style>
</head>
<body>

<h1>CRIA UMA SENHA NOVA, CUIUDO</h1>

<?php if ($msg) echo "<p style='color: greenyellow;'>$msg <a href='login.php' style='color: lime;'>Ir pro login</a></p>"; ?>
<?php if ($erro) echo "<p style='color: red;'>$erro</p>"; ?>

<?php if ($token_valido): ?>
<form method="post">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

    <label>Nova Senha</label>
    <input name="senha1" size="25" type="password" required placeholder="Mín. 8 caracteres, 1 maiúscula, 1 número, 1 símbolo">
    <br><br>

    <label>Confirma a Senha</label>
    <input name="senha2" size="25" type="password" required placeholder="Repita a senha">
    <br><br>

    <button type="submit" name="nova_senha">Trocar senha!</button>
</form>
<?php else: ?>
    <?php if (!$msg): ?>
    <br>
    <form method="post" action="senha.php">
        <button type="submit">Pedir novo link</button>
    </form>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
