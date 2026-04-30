<?php
session_start();
include "conexao.php";
include "login.html";

// Função reciclada do cadastro (validaCPF)
function validaCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

$erro = "";

// Nova função: tenta achar o cuido no banco
function cacarCuiudo($conexao, $login_digitado, $senha_digitada) {
    $sql = "SELECT emailnumero, senha FROM `usuarios`";
    $query = mysqli_query($conexao, $sql);
    
    // Limpa o login caso seja CPF (pra comparar com hash depois)
    $cpf_sem_mascara = preg_replace('/[^0-9]/', '', $login_digitado);
    $eh_cpf_valido = validaCPF($login_digitado); // true se for CPF de verdade
    
    while ($registro = mysqli_fetch_assoc($query)) {
        $campo = $registro['emailnumero'];
        
        // Se o campo começa com '$2y$' (marca do password_hash) → CPF criptografado
        if (str_starts_with($campo, '$2y$')) {
            // Só confere se o cara digitou um CPF válido E o hash bate
            if ($eh_cpf_valido && password_verify($cpf_sem_mascara, $campo)) {
                // Beleza, agora vê se a senha também tá certa
                if (password_verify($senha_digitada, $registro['senha'])) {
                    return $registro; // achou o danado
                }
            }
        } else {
            // É email ou telefone (guardado em texto puro)
            if ($campo === $login_digitado && password_verify($senha_digitada, $registro['senha'])) {
                return $registro; // achou!
            }
        }
    }
    
    return false; // não achou ninguém :(
}

$erro = '';

if (isset($_POST['entrar'])) {
    $login_do_macho = trim($_POST['emailnumero']);
    $senha_do_macho = trim($_POST['senha']);
    
    // Chama a caçadora
    $cuiudo = cacarCuiudo($conexao, $login_do_macho, $senha_do_macho);
    
    if ($cuiudo) {
        // Guarda o identificador na sessão (pode ser CPF, email ou telefone original)
        $_SESSION['usuario_login'] = $cuiudo['emailnumero'];
        // Redireciona pra onde você quiser (ex.: formulario.php, dashboard.php...)
        header("Location: formulario.php");
        exit;
    } else {
        $erro = "Oxe, login ou senha errada, Zé! Tenta de novo aí.";
    }
}
?>