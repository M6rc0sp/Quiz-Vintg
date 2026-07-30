<?php
/**
 * Exporta leads para CSV
 * Respeita os mesmos filtros do dashboard
 */

require_once __DIR__ . '/config.php';

// ---- Filtros (mesma lógica do dashboard) ----
$search   = trim($_GET['search'] ?? '');
$profile  = trim($_GET['profile'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(name LIKE ? OR email LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($profile !== '' && in_array($profile, ['A', 'B', 'C', 'D'])) {
    $where[]  = 'profile_key = ?';
    $params[] = $profile;
}
if ($dateFrom !== '') {
    $where[]  = 'created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[]  = 'created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, name, email, profile_key, profile_name, answers, created_at
        FROM leads
        {$whereClause}
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $leads = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao exportar: ' . $e->getMessage());
}

// Nome do arquivo
$filename = 'leads_quiz_vintg_' . date('Y-m-d') . '.csv';

// Headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output buffer para escrita
$output = fopen('php://output', 'w');

// BOM UTF-8 para Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabeçalho
fputcsv($output, [
    'ID',
    'Nome',
    'E-mail',
    'Perfil',
    'Nome do Perfil',
    'Data',
    'Respostas (JSON)',
]);

foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['id'],
        $lead['name'],
        $lead['email'],
        $lead['profile_key'],
        $lead['profile_name'],
        $lead['created_at'],
        $lead['answers'],
    ]);
}

fclose($output);
