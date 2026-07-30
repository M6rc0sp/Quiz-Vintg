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
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --red: #35050A;
    --red-soft: #5A1219;
    --cream: #FFFFE7;
    --cream-dim: #F3F3D9;
    --black: #17130F;
    --body: #241C17;
    --brass: #A9824C;
    --brass-soft: #D8C9A3;
    --line: rgba(23,19,15,0.14);
  }
  *{ box-sizing:border-box; margin:0; padding:0; }

  body{
    background: var(--cream);
    color: var(--black);
    font-family: 'Jost', sans-serif;
    min-height:100vh;
    padding: 32px 14px;
    -webkit-font-smoothing: antialiased;
  }
  .container{ max-width:900px; margin:0 auto; }

  .badge-row{ text-align:center; margin-bottom: 20px; }
  .badge{
    display:inline-flex; align-items:center; gap:6px;
    background: var(--red); color: var(--cream);
    font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
    padding: 7px 16px;
  }

  .card{
    background: var(--cream);
    border: 1px solid var(--line);
    padding: 40px 32px 34px;
    position: relative;
  }
  .card::before{
    content:"";
    position:absolute;
    top:12px; left:12px; right:12px; bottom:12px;
    border: 1px solid var(--brass-soft);
    pointer-events:none;
  }
  .card-header{
    display:flex; justify-content:space-between; align-items:flex-start;
    flex-wrap:wrap; gap:12px; margin-bottom: 28px;
  }
  .card-header h1{
    font-family:'Lora', serif;
    font-weight: 500;
    font-style: italic;
    font-size: clamp(23px, 4.2vw, 30px);
    color: var(--red);
    margin-bottom: 4px;
  }
  .card-header .subtitle{
    font-size: 15px; color: var(--body); opacity:0.75;
  }
  .card-actions{ display:flex; gap:8px; flex-wrap:wrap; }

  /* ---- Stats ---- */
  .stats{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
  }
  .stat-card{
    background: var(--cream);
    border: 1px solid var(--line);
    padding: 18px 16px;
    position:relative;
  }
  .stat-card::before{
    content:"";
    position:absolute;
    top:6px; left:6px; right:6px; bottom:6px;
    border: 1px solid var(--brass-soft);
    pointer-events:none;
  }
  .stat-card .label{
    font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--brass); margin-bottom: 4px;
  }
  .stat-card .value{
    font-family:'Lora', serif; font-style: italic;
    font-size: 28px; font-weight: 600;
    line-height: 1.2;
    color: var(--red);
  }
  .stat-card .value .dot{
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    vertical-align: middle;
    border: 1px solid rgba(0,0,0,0.08);
  }

  /* ---- Filters ---- */
  .filters{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    background: var(--cream);
    border: 1px solid var(--line);
    padding: 16px 18px;
    margin-bottom: 20px;
    position:relative;
  }
  .filters label{
    font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--brass); white-space: nowrap;
  }
  .filters input, .filters select{
    padding: 8px 12px;
    border: 1px solid var(--line);
    background: var(--cream);
    font-family:'Jost', sans-serif;
    font-size: 14px;
    color: var(--body);
    min-width: 150px;
  }
  .filters input:focus, .filters select:focus{
    outline: none;
    border-color: var(--red);
  }
  .filters input::placeholder{ color: rgba(23,19,15,0.35); }

  .btn{
    padding: 9px 18px;
    border: none;
    font-family:'Jost', sans-serif;
    font-size: 12.5px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background 0.25s ease;
  }
  .btn-primary{
    background: var(--red); color: var(--cream);
  }
  .btn-primary:hover{ background: var(--red-soft); }
  .btn-outline{
    background: transparent;
    border: 1px solid var(--line);
    color: var(--black);
  }
  .btn-outline:hover{ border-color: var(--brass); color: var(--red); }
  .btn-sm{ padding: 6px 14px; font-size: 11px; }

  /* ---- Table ---- */
  .table-wrap{
    border: 1px solid var(--line);
    overflow: hidden;
    position:relative;
  }
  table{
    width:100%;
    border-collapse: collapse;
  }
  th{
    text-align: left;
    padding: 14px 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--brass);
    background: var(--cream-dim);
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
    font-weight: 500;
  }
  td{
    padding: 14px 16px;
    font-size: 14.5px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    background: var(--cream);
    color: var(--body);
  }
  tr:hover td{ background: var(--cream-dim); }

  .badge-profile{
    display: inline-block;
    padding: 2px 10px;
    font-size: 11.5px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #fff;
    white-space: nowrap;
  }

  .action-links a{
    color: var(--brass);
    text-decoration: none;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }
  .action-links a:hover{ color: var(--red); }

  /* ---- Pagination ---- */
  .pagination{
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: var(--body);
    opacity:0.8;
    background: var(--cream);
    border-top: 1px solid var(--line);
  }
  .pagination a{
    color: var(--red);
    text-decoration: none;
    padding: 5px 10px;
    border: 1px solid var(--line);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin: 0 2px;
    transition: border-color 0.2s;
  }
  .pagination a:hover{ border-color: var(--brass); }
  .pagination .current{
    background: var(--red);
    color: var(--cream);
    border-color: var(--red);
    padding: 5px 10px;
    margin: 0 2px;
    font-size: 12px;
  }

  .empty{
    text-align: center;
    padding: 48px 16px;
    color: var(--body); opacity:0.55;
  }
  .empty p{ font-size: 16px; margin-bottom: 8px; }

  /* ---- Modal ---- */
  .modal-overlay{
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(53,5,10,0.45);
    z-index: 100;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-overlay.active{ display: flex; }
  .modal{
    background: var(--cream);
    border: 1px solid var(--line);
    max-width: 640px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    position:relative;
  }
  .modal::before{
    content:"";
    position:absolute;
    top:8px; left:8px; right:8px; bottom:8px;
    border: 1px solid var(--brass-soft);
    pointer-events:none;
  }
  .modal h2{
    font-family:'Lora', serif; font-style: italic; font-weight: 500;
    font-size: 24px; color: var(--red); margin-bottom: 4px;
  }
  .modal .close{
    float: right;
    background: none; border: none;
    font-size: 26px; cursor: pointer;
    color: var(--brass); position:relative; z-index:1;
  }
  .modal .close:hover{ color: var(--red); }
  .modal-section{ margin-bottom: 20px; position:relative; z-index:1; }
  .modal-section h3{
    font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--brass); margin-bottom: 6px;
  }
  .modal-section p{ font-size: 15px; line-height: 1.6; color: var(--body); }
  .answer-item{
    display: flex;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--line);
    font-size: 14px;
  }
  .answer-item .q{ color: var(--body); opacity:0.75; flex:1; }
  .answer-item .a{ font-weight: 500; color: var(--red-soft); }
</style>
</head>
<body>

<div class="container">

  <div class="badge-row">
    <span class="badge">✦ Dashboard · Quiz Vintg</span>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h1>Leads captados</h1>
        <p class="subtitle">Gerencie os formulários enviados do quiz</p>
      </div>
      <div class="card-actions">
        <a href="export.php<?= $search ? "?search=" . urlencode($search) : '' ?>" class="btn btn-primary">📥 Exportar CSV</a>
        <button class="btn btn-outline" onclick="window.location.reload()">🔄 Atualizar</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="label">Total de Leads</div>
        <div class="value"><?= number_format($totalLeads, 0, ',', '.') ?></div>
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
              <td style="color:var(--body); opacity:0.5; font-size:13px;"><?= $lead['id'] ?></td>
              <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
              <td><a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color:var(--brass);text-decoration:none;"><?= htmlspecialchars($lead['email']) ?></a></td>
              <td>
                <span class="badge-profile" style="background:<?= $profileColors[$lead['profile_key']] ?>">
                  <?= htmlspecialchars($lead['profile_key']) ?> — <?= htmlspecialchars($lead['profile_name']) ?>
                </span>
              </td>
              <td style="white-space:nowrap; opacity:0.65; font-size:13px;">
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
    </div><!-- /table-wrap -->
  </div><!-- /card -->
</div><!-- /container -->

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
