<?php
/**
 * Configuração do banco SQLite
 *
 * O arquivo .sqlite será criado automaticamente na pasta
 * do projeto na primeira execução.
 */

define('DB_PATH', __DIR__ . '/../quiz_vintg.sqlite');

/** Retorna uma conexão PDO SQLite */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Cria a tabela automaticamente se não existir
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                profile_key TEXT NOT NULL CHECK(profile_key IN (\'A\',\'B\',\'C\',\'D\')),
                profile_name TEXT NOT NULL,
                answers TEXT NOT NULL DEFAULT \'[]\',
                user_agent TEXT DEFAULT NULL,
                ip_address TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT (datetime(\'now\', \'localtime\'))
            )
        ');

        // WAL mode = melhor performance
        $pdo->exec('PRAGMA journal_mode=WAL');
    }

    return $pdo;
}
