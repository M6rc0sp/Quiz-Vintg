<?php
/**
 * Retorna HTML com os detalhes de um lead específico
 * Chamado via fetch pelo dashboard.php
 */

require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo '<p style="color:red;">ID inválido.</p>';
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $lead = $stmt->fetch();

    if (!$lead) {
        echo '<p>Lead não encontrado.</p>';
        exit;
    }

    $answers = json_decode($lead['answers'], true) ?: [];
    $profileEmoji = ['A' => '🤎', 'B' => '🔥', 'C' => '✨', 'D' => '💐'];
    $emoji = $profileEmoji[$lead['profile_key']] ?? '🎯';
?>
<h2><?= htmlspecialchars($lead['name']) ?> <?= $emoji ?></h2>
<p style="color:#6b655d; font-size:14px; margin-bottom:20px;">
  <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color:#A9824C;"><?= htmlspecialchars($lead['email']) ?></a>
  &middot; <?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?>
</p>

<div class="modal-section">
  <h3>Perfil do Quiz</h3>
  <p><strong><?= htmlspecialchars($lead['profile_key']) ?></strong> — <?= htmlspecialchars($lead['profile_name']) ?></p>
</div>

<div class="modal-section">
  <h3>Respostas (<?= count($answers) ?> perguntas)</h3>
  <?php if (count($answers) === 0): ?>
    <p style="color:#8a8379;">Nenhuma resposta registrada.</p>
  <?php else: ?>
    <?php foreach ($answers as $idx => $ans): ?>
      <div class="answer-item">
        <span class="q">
          <?php if (is_array($ans) && isset($ans['question'])): ?>
            <strong>P<?= $idx + 1 ?>:</strong> <?= htmlspecialchars($ans['question']) ?>
          <?php else: ?>
            <strong>P<?= $idx + 1 ?>:</strong> Pergunta <?= $idx + 1 ?>
          <?php endif; ?>
        </span>
        <span class="a">
          <?php if (is_array($ans)): ?>
            <?php if (isset($ans['answer'])): ?>
              <?= htmlspecialchars($ans['answer']) ?>
            <?php elseif (isset($ans['text'])): ?>
              <?= htmlspecialchars($ans['text']) ?>
            <?php else: ?>
              <?= htmlspecialchars(json_encode($ans, JSON_UNESCAPED_UNICODE)) ?>
            <?php endif; ?>
          <?php else: ?>
            <?= htmlspecialchars((string)$ans) ?>
          <?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal-section" style="margin-top:16px; padding-top:16px; border-top:1px solid #f0ece6;">
  <h3>Metadados</h3>
  <p style="font-size:13px; color:#6b655d;">
    IP: <?= htmlspecialchars($lead['ip_address'] ?? '—') ?><br>
    User-Agent: <?= htmlspecialchars($lead['user_agent'] ?? '—') ?>
  </p>
</div>
<?php
} catch (PDOException $e) {
    echo '<p style="color:red;">Erro ao carregar dados.</p>';
    error_log('lead_detail.php: ' . $e->getMessage());
}
