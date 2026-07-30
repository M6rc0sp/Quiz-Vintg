<?php
/**
 * Endpoint para receber os dados do quiz via POST (JSON)
 *
 * Exemplo de uso (front-end):
 *   fetch('backend/submit.php', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/json' },
 *     body: JSON.stringify({ name, email, profileKey, profileName, answers })
 *   })
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

require_once __DIR__ . '/config.php';

// Lê o body da requisição
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos. E-mail é obrigatório.']);
    exit;
}

$name        = trim($data['name'] ?? '');
$email       = trim($data['email']);
$profileKey  = strtoupper(trim($data['profileKey'] ?? ''));
$profileName = trim($data['profileName'] ?? '');
$answers     = $data['answers'] ?? [];

// Validações básicas
if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nome é obrigatório.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'E-mail inválido.']);
    exit;
}

if (!in_array($profileKey, ['A', 'B', 'C', 'D'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Perfil inválido.']);
    exit;
}

try {
    $pdo = getDB();

    // Verifica se o e-mail já foi registrado (evita duplicatas)
    $stmt = $pdo->prepare('SELECT id FROM leads WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Este e-mail já foi registrado no quiz.']);
        exit;
    }

    // Insere o lead
    $stmt = $pdo->prepare('
        INSERT INTO leads (name, email, profile_key, profile_name, answers, user_agent, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $name,
        $email,
        $profileKey,
        $profileName,
        json_encode($answers, JSON_UNESCAPED_UNICODE),
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $leadId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Lead registrado com sucesso!',
        'lead_id' => (int) $leadId,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno no servidor.']);
    // Log do erro real (não expor em produção)
    error_log('submit.php - PDOException: ' . $e->getMessage());
}
