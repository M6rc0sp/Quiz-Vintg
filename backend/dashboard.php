<?php
/**
 * Dashboard de Leads do Quiz Vintg
 *
 * Requer: PHP 7.4+ com extensão PDO SQLite
 */

require_once __DIR__ . '/config.php';

// ---- Filtros ----
$search   = trim($_GET['search'] ?? '');
$profile  = trim($_GET['profile'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;

// ---- Busca ----
$where  = [];
$params = [];

if ($search !== '') {
    $where[]      = '(name LIKE ? OR email LIKE ?)';
    $params[]     = "%{$search}%";
    $params[]     = "%{$search}%";
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

    // Total para paginação
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM leads {$whereClause}");
    $totalStmt->execute($params);
    $totalRows = (int) $totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    // Dados da página
    $stmt = $pdo->prepare("
        SELECT id, name, email, profile_key, profile_name, created_at
        FROM leads
        {$whereClause}
        ORDER BY created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $leads = $stmt->fetchAll();

    // Totais por perfil (para card)
    $profileCounts = $pdo->query("
        SELECT profile_key, COUNT(*) AS total
        FROM leads
        GROUP BY profile_key
        ORDER BY total DESC
    ")->fetchAll();
    $profileCountsIndexed = [];
    foreach ($profileCounts as $row) {
        $profileCountsIndexed[$row['profile_key']] = (int) $row['total'];
    }

    $totalLeads = array_sum($profileCountsIndexed);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco: ' . $e->getMessage());
}

// Nomes dos perfis
$profileNames = [
    'A' => 'Clássica Atemporal',
    'B' => 'Ousada & Poderosa',
    'C' => 'Vintage & Autêntica',
    'D' => 'Romântica & Delicada',
];

$profileColors = [
    'A' => '#A9824C',
    'B' => '#5A1219',
    'C' => '#17130F',
    'D' => '#D8C9A3',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Quiz Vintg</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
<style>
  *{ box-sizing:border-box; margin:0; padding:0; }
  body{
    font-family: 'Inter', system-ui, sans-serif;
    background: #f5f3ef;
    color: #17130F;
    padding: 32px 24px;
  }
  .container{ max-width:1280px; margin:0 auto; }

  h1{
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 4px;
  }
  .subtitle{
    color: #6b655d;
    font-size: 15px;
    margin-bottom: 28px;
  }

  /* ---- Stats cards ---- */
  .stats{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
  }
  .stat-card{
    background: #fff;
    border-radius: 10px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  .stat-card .label{
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8a8379;
    margin-bottom: 6px;
  }
  .stat-card .value{
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
  }
  .stat-card .value .dot{
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    vertical-align: middle;
  }

  /* ---- Filters ---- */
  .filters{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    background: #fff;
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  .filters label{ font-size: 13px; color: #555; white-space: nowrap; }
  .filters input, .filters select{
    padding: 8px 12px;
    border: 1px solid #d6d0c8;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    background: #fff;
    min-width: 160px;
  }
  .filters input:focus, .filters select:focus{
    outline: none;
    border-color: #A9824C;
  }
  .btn{
    padding: 8px 18px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
  }
  .btn-primary{
    background: #35050A;
    color: #FFFFE7;
  }
  .btn-primary:hover{ background: #5A1219; }
  .btn-outline{
    background: transparent;
    border: 1px solid #d6d0c8;
    color: #444;
  }
  .btn-outline:hover{ border-color: #A9824C; color: #35050A; }
  .btn-danger{
    background: #dc2626;
    color: #fff;
  }
  .btn-danger:hover{ background: #b91c1c; }
  .btn-sm{ padding: 6px 12px; font-size: 13px; }

  /* ---- Table ---- */
  .table-wrap{
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  table{
    width:100%;
    border-collapse: collapse;
  }
  th{
    text-align: left;
    padding: 14px 16px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8a8379;
    background: #faf9f6;
    border-bottom: 1px solid #e8e3dc;
    white-space: nowrap;
  }
  td{
    padding: 14px 16px;
    font-size: 14px;
    border-bottom: 1px solid #f0ece6;
    vertical-align: middle;
  }
  tr:hover td{ background: #faf9f6; }

  .badge-profile{
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    color: #fff;
    white-space: nowrap;
  }

  .action-links a{
    color: #A9824C;
    text-decoration: none;
    font-size: 13px;
    margin-right: 10px;
  }
  .action-links a:hover{ text-decoration: underline; color: #35050A; }

  /* ---- Pagination ---- */
  .pagination{
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #555;
  }
  .pagination a{
    color: #35050A;
    text-decoration: none;
    padding: 6px 12px;
    border: 1px solid #d6d0c8;
    border-radius: 6px;
    margin: 0 2px;
  }
  .pagination a:hover{ border-color: #A9824C; }
  .pagination .current{
    background: #35050A;
    color: #fff;
    border-color: #35050A;
    padding: 6px 12px;
    border-radius: 6px;
    margin: 0 2px;
  }

  .empty{
    text-align: center;
    padding: 48px 16px;
    color: #8a8379;
  }
  .empty p{ font-size: 16px; margin-bottom: 8px; }

  /* ---- Modal ---- */
  .modal-overlay{
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 100;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-overlay.active{ display: flex; }
  .modal{
    background: #fff;
    border-radius: 12px;
    max-width: 640px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  }
  .modal h2{ font-size: 22px; margin-bottom: 4px; }
  .modal .close{
    float: right;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #8a8379;
  }
  .modal .close:hover{ color: #17130F; }
  .modal-section{ margin-bottom: 20px; }
  .modal-section h3{
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #8a8379;
    margin-bottom: 8px;
  }
  .modal-section p{ font-size: 15px; line-height: 1.6; }
  .answer-item{
    display: flex;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0ece6;
    font-size: 14px;
  }
  .answer-item .q{ color: #555; flex:1; }
  .answer-item .a{ font-weight: 500; }
</style>
</head>
<body>

<div class="container">

  <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
    <div>
      <h1>📊 Quiz Vintg — Leads</h1>
      <p class="subtitle">Dashboard de captação de leads</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="export.php<?= $search ? "?search=" . urlencode($search) : '' ?>" class="btn btn-primary">📥 Exportar CSV</a>
      <button class="btn btn-outline" onclick="window.location.reload()">🔄 Atualizar</button>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="label">Total de Leads</div>
      <div class="value" style="color:#35050A;"><?= number_format($totalLeads, 0, ',', '.') ?></div>
    </div>
    <?php foreach (['A' => 'Clássica', 'B' => 'Ousada', 'C' => 'Vintage', 'D' => 'Romântica'] as $k => $label): ?>
    <div class="stat-card">
      <div class="label"><?= $label ?></div>
      <div class="value">
        <span class="dot" style="background:<?= $profileColors[$k] ?>"></span>
        <?= number_format($profileCountsIndexed[$k] ?? 0, 0, ',', '.') ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filters -->
  <form class="filters" method="GET">
    <label>Buscar</label>
    <input type="text" name="search" placeholder="Nome ou e-mail..." value="<?= htmlspecialchars($search) ?>">

    <label>Perfil</label>
    <select name="profile">
      <option value="">Todos</option>
      <?php foreach ($profileNames as $k => $n): ?>
        <option value="<?= $k ?>" <?= $profile === $k ? 'selected' : '' ?>><?= $n ?></option>
      <?php endforeach; ?>
    </select>

    <label>De</label>
    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">

    <label>Até</label>
    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">

    <button type="submit" class="btn btn-primary">Filtrar</button>
    <?php if ($search || $profile || $dateFrom || $dateTo): ?>
      <a href="dashboard.php" class="btn btn-outline">Limpar</a>
    <?php endif; ?>
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th>Data</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($leads) === 0): ?>
        <tr><td colspan="6">
          <div class="empty">
            <p>😕 Nenhum lead encontrado</p>
            <p style="font-size:14px;">Os dados aparecerão aqui quando alguém preencher o quiz.</p>
          </div>
        </td></tr>
        <?php else: ?>
          <?php foreach ($leads as $lead): ?>
          <tr>
            <td style="color:#8a8379; font-size:13px;"><?= $lead['id'] ?></td>
            <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
            <td><a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color:#A9824C;text-decoration:none;"><?= htmlspecialchars($lead['email']) ?></a></td>
            <td>
              <span class="badge-profile" style="background:<?= $profileColors[$lead['profile_key']] ?>">
                <?= htmlspecialchars($lead['profile_key']) ?> — <?= htmlspecialchars($lead['profile_name']) ?>
              </span>
            </td>
            <td style="white-space:nowrap; color:#6b655d; font-size:13px;">
              <?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?>
            </td>
            <td class="action-links">
              <a href="#" onclick="viewLead(<?= $lead['id'] ?>); return false;">👁 Ver</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <span><?= number_format($totalRows, 0, ',', '.') ?> registro(s) — Página <?= $page ?> de <?= $totalPages ?></span>
      <div>
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&profile=<?= $profile ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">← Anterior</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
          <?= $i === $page ? '<span class="current">' . $i . '</span>' : '<a href="?page=' . $i . '&search=' . urlencode($search) . '&profile=' . $profile . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '">' . $i . '</a>' ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&profile=<?= $profile ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">Próxima →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Lead Detail -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <button class="close" onclick="closeModal()">&times;</button>
    <div id="modalContent"></div>
  </div>
</div>

<script>
async function viewLead(id){
  const overlay = document.getElementById('modalOverlay');
  const content = document.getElementById('modalContent');
  content.innerHTML = '<p style="text-align:center;padding:20px;">Carregando...</p>';
  overlay.classList.add('active');

  try{
    const res = await fetch(`lead_detail.php?id=${id}`);
    const html = await res.text();
    content.innerHTML = html;
  }catch(e){
    content.innerHTML = '<p style="color:red;">Erro ao carregar detalhes.</p>';
  }
}

function closeModal(){
  document.getElementById('modalOverlay').classList.remove('active');
}

// Fecha modal ao clicar fora
document.getElementById('modalOverlay').addEventListener('click', function(e){
  if(e.target === this) closeModal();
});

// Fecha com ESC
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeModal();
});
</script>
</body>
</html>
