<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';

$usuario = exigirLogin();

// Quantas reservas há em cada status
$contagem = array_fill_keys(array_keys(STATUS_RESERVA), 0);
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM reservas GROUP BY status') as $linha) {
    $contagem[$linha['status']] = (int) $linha['total'];
}

// Check-ins de hoje que ainda ocupam quarto
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM reservas WHERE checkin = :hoje AND status IN ('pendente', 'confirmada')"
);
$stmt->execute(['hoje' => date('Y-m-d')]);
$checkinsHoje = (int) $stmt->fetchColumn();

// As 5 reservas mais recentes
$recentes = $pdo->query(
    'SELECT r.codigo, r.hospede_nome, r.checkin, r.checkout, r.valor_total, r.status,
            t.nome AS quarto, h.nome AS hotel
       FROM reservas r
       JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
       JOIN hoteis h       ON h.id = t.hotel_id
      ORDER BY r.id DESC
      LIMIT 5'
)->fetchAll();


$titulo      = 'Painel';
$paginaAtual = 'painel';
require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">
  <h1>Olá, <?= e(explode(' ', $usuario['nome'])[0]) ?></h1>
  <p class="subtitulo">Resumo das reservas.</p>

  <div class="painel-numeros">
    <div class="numero numero--destaque">
      <span class="numero__valor"><?= $checkinsHoje ?></span>
      <span class="numero__rotulo">Check-ins hoje</span>
    </div>
    <?php foreach (STATUS_RESERVA as $chave => $rotulo): ?>
      <div class="numero">
        <span class="numero__valor"><?= $contagem[$chave] ?></span>
        <span class="numero__rotulo"><?= e($rotulo) ?>s</span>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 style="margin-top:2.5rem">Últimas reservas</h2>

  <?php if (empty($recentes)): ?>
    <p>Nenhuma reserva ainda.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr>
            <th>Código</th>

            <th>Hóspede</th>
            <th>Hotel / Quarto</th>
            <th>Período</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentes as $r): ?>
            <tr>
              <td><strong><?= e($r['codigo']) ?></strong></td>
              <td><?= e($r['hospede_nome']) ?></td>
              <td><?= e($r['hotel']) ?><br><small><?= e($r['quarto']) ?></small></td>
              <td><?= data_br($r['checkin']) ?> a <?= data_br($r['checkout']) ?></td>
              <td><?= moeda($r['valor_total']) ?></td>
              <td><span class="status status--<?= e($r['status']) ?>"><?= e(rotuloStatus($r['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';