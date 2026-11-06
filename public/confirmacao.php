<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';
require __DIR__ . '/../includes/reservas.php';

// O código chega pela URL (?codigo=GOA7K2QX). Só aceitamos o formato exato.
$codigo = isset($_GET['codigo']) && is_string($_GET['codigo']) ? strtoupper(trim($_GET['codigo'])) : '';

$reserva = null;
if (preg_match('/^[A-Z0-9]{8}$/', $codigo)) {
    $stmt = $pdo->prepare(
        'SELECT r.codigo, r.hospede_nome, r.checkin, r.checkout, r.adultos, r.criancas,
                r.valor_total, r.status,
                t.nome AS quarto, h.nome AS hotel, h.cidade, h.uf
           FROM reservas r
           JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
           JOIN hoteis h       ON h.id = t.hotel_id
          WHERE r.codigo = :codigo'
    );
    $stmt->execute(['codigo' => $codigo]);
    $reserva = $stmt->fetch() ?: null;
}

$paginaAtual = 'hoteis';

if (!$reserva) {
    http_response_code(404);
    $titulo = 'Reserva não encontrada';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="container secao">
      <h1>Reserva não encontrada</h1>

      <p>Não encontramos nenhuma reserva com esse código.</p>
      <a class="btn btn--destaque" href="<?= url() ?>">Voltar ao início</a>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$noites = contarNoites($reserva['checkin'], $reserva['checkout']);

$titulo = 'Reserva ' . $reserva['codigo'];
require __DIR__ . '/../includes/header.php';
?>

<section class="hero hero--hotel">
  <div class="container">
    <p class="hero__etiqueta">Reserva registrada</p>
    <h1>Obrigado, <?= e(explode(' ', $reserva['hospede_nome'])[0]) ?>!</h1>
    <p class="hero__texto"><?= e(mensagemStatus($reserva['status'])) ?></p>
  </div>
</section>

<section class="container secao">
  <div class="cartao">
    <p class="subtitulo">Código da sua reserva</p>
    <p class="codigo-reserva"><?= e($reserva['codigo']) ?></p>
    <p><span class="status status--<?= e($reserva['status']) ?>"><?= e(rotuloStatus($reserva['status'])) ?></span></p>

    <dl class="detalhes">
      <dt>Hotel</dt>
      <dd><?= e($reserva['hotel']) ?> &middot; <?= e($reserva['cidade']) ?>/<?= e($reserva['uf']) ?></dd>
      <dt>Quarto</dt>

      <dd><?= e($reserva['quarto']) ?></dd>
      <dt>Check-in</dt>
      <dd><?= data_br($reserva['checkin']) ?></dd>
      <dt>Check-out</dt>
      <dd><?= data_br($reserva['checkout']) ?> (<?= plural($noites, 'noite', 'noites') ?>)</dd>
      <dt>Hóspedes</dt>
      <dd><?= plural((int) $reserva['adultos'], 'adulto', 'adultos') ?><?php if ((int) $reserva['criancas'] > 0): ?>, <?= plural((int) $reserva['criancas'], 'criança', 'crianças') ?><?php endif; ?></dd>
      <dt>Total</dt>
      <dd><strong><?= moeda($reserva['valor_total']) ?></strong></dd>
    </dl>

    <p class="subtitulo">Guarde este código: ele identifica a sua reserva.</p>

    <div class="form-acoes">
      <a class="btn btn--destaque" href="<?= url() ?>">Voltar ao início</a>
      <button type="button" class="btn btn--contorno" onclick="window.print()">Imprimir</button>
    </div>
  </div>
</section>

<?php
require __DIR__ . '/../includes/footer.php';