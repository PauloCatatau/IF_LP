<?php
include "conexao.php";

// ── PHPMailer via Composer ───────────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// ── CONFIGURAÇÕES DO GMAIL ───────────────────────────────────────────────────
// 1. Use o email do Gmail que VAI ENVIAR o aviso
// 2. Em vez da senha normal, crie uma "Senha de app" do Google:
//    myaccount.google.com > Segurança > Verificação em duas etapas > Senhas de app
define('GMAIL_USER', 'paulocatatau5@gmail.com'); // email que envia
define('GMAIL_PASS', 'zjed bhuq jquv xowq'); // senha de app (16 chars sem espaço)

$msg  = "";
$erro = "";

if (isset($_POST['recuperar'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Bota um email de verdade aí macho! >:(";
    } else {
        $stmt = $conexao->prepare("SELECT id FROM cad_user WHERE emailnumero = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Registra a intenção antes de tentar conectar ao SMTP.
            $log = $conexao->prepare("INSERT INTO historico_recuperacao (email, enviado) VALUES (?, 0)");
            $log->bind_param("s", $email);
            $log->execute();
            $historico_id = $conexao->insert_id;
            $log->close();

            // Gera token seguro
            $token     = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Apaga tokens antigos e insere novo
            $del = $conexao->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            $ins = $conexao->prepare("INSERT INTO recuperacao_senha (email, token, expiracao) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expiracao);
            $ins->execute();

            $link = "http://localhost/tarefinhahugofofo/nova_senha.php?token=$token";

            // ── Envio via PHPMailer + Gmail SMTP ────────────────────────────
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = GMAIL_USER;
                $mail->Password   = GMAIL_PASS;
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom(GMAIL_USER, 'Cuiudo System');
                $mail->addAddress('paulocatatau5@gmail.com');

                $mail->Subject = 'Recuperação de senha - Cuiudo System';
                $mail->Body    =
                    "E aí cuiudo!\n\n" .
                    "Alguém (provavelmente você, né) pediu pra trocar a senha.\n\n" .
                    "Clica no link abaixo pra criar uma nova senha (válido por 1 hora):\n\n" .
                    "$link\n\n" .
                    "Se não foi você, ignora esse email e segue o baile.";

                $mail->send();
                $log = $conexao->prepare("UPDATE historico_recuperacao SET enviado = 1 WHERE id = ?");
                $log->bind_param("i", $historico_id);
                $log->execute();
                $log->close();
                $msg = "Email enviado pro cuiudo paulocatatau5@gmail.com! Vai lá verificar.";
            } catch (Exception $e) {
                $erro = "Erro ao enviar email: " . $mail->ErrorInfo;
            }

        } else {
            $msg = "Se esse email existir, você vai receber as instruções. Vai lá verificar.";
        }

        $stmt->close();
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

<h1>ESQUECEU A SENHA? QUE CUIUDO...</h1>

<?php if ($msg) echo "<p style='color: greenyellow;'>$msg</p>"; ?>
<?php if ($erro) echo "<p style='color: red;'>$erro</p>"; ?>

<form method="post">
    <label>Seu Email</label><br><br>
    <input name="email" size="25" type="email" required placeholder="seuemail@email.com" autocomplete="off">
    <br><br>
    <button type="submit" name="recuperar" id="botao-recuperar">Manda o link aí!</button>
</form>

<script>
document.querySelector('form').addEventListener('submit', function () {
    const botao = document.getElementById('botao-recuperar');
    botao.disabled = true;
    botao.textContent = 'Enviando...';
});
</script>

<br>

<form method="post" action="login.php">
    <button type="submit">Volta pro login</button>
</form>

</body>
</html>
