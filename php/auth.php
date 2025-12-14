<?php
session_start();
require 'config.php';

// Verifica se veio dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao']; // 'login' ou 'registrar'

    // --- REGISTRAR NOVO USUÁRIO ---
    if ($acao === 'registrar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // Verifica se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<script>alert('Email já cadastrado!'); window.location.href='../index.html';</script>";
            exit;
        }

        // Criptografa a senha (Segurança básica)
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        // Insere no banco
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        if ($stmt->execute([$nome, $email, $hashSenha])) {
            echo "<script>alert('Conta criada! Faça login.'); window.location.href='../index.html';</script>";
        } else {
            echo "Erro ao registrar.";
        }
    }

    // --- FAZER LOGIN ---
    elseif ($acao === 'login') {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se achou usuário e se a senha bate com o hash
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // LOGIN SUCESSO!
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            
            // Redireciona para o Dashboard (que criaremos depois)
            header("Location: ../dashboard.php");
            exit;
        } else {
            echo "<script>alert('Email ou senha incorretos!'); window.location.href='../index.html';</script>";
        }
    }
}
?>