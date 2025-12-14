<?php
// Define o caminho do banco de dados (na pasta database)
$dbPath = __DIR__ . '/../database/rpg.db';

try {
    // Conecta ao SQLite (cria o arquivo se não existir)
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Habilita chaves estrangeiras (importante para relacionar ficha com usuário)
    $pdo->exec("PRAGMA foreign_keys = ON");

    // --- CRIAÇÃO AUTOMÁTICA DAS TABELAS ---

    // 1. Tabela de Usuários
    $queryUsuarios = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($queryUsuarios);

    // 2. Tabela de Fichas (Guarda o JSON inteiro dentro de uma coluna texto)
    $queryFichas = "
    CREATE TABLE IF NOT EXISTS fichas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL,
        nome_personagem TEXT,
        dados_json TEXT,
        data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )";
    $pdo->exec($queryFichas);

} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}
?>