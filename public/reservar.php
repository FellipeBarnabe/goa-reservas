<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';
require __DIR__ . '/../includes/reservas.php';

$ctx = carregarContextoReserva($pdo, $_GET);

// Sem hotel válido, volta para a busca da página inicial
if (!$ctx['hotel']) {
    redirecionar('index.php');
}

$hotel   = $ctx['hotel'];
$periodo = $ctx['periodo'];
$hoje    = date('Y-m-d');

$titulo      = 'Reservar';
$paginaAtual = 'hoteis';
require __DIR__ . '/../includes/header.php';
?>

<section class="hero hero--hotel">
  <div class="container">
    <p class="hero__etiqueta">Reserva &middot; <?= e($hotel['bandeira']) ?></p>
    <h1><?= e($hotel['nome']) ?></h1>
    <p class="hero__texto"><?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?></p>
  </div>
</section>

<section class="container secao">

  <?php if (!empty($ctx['erros'])): ?>

    <div class="alerta alerta--erro" role="alert">
      <strong>Confira as datas e os hóspedes:</strong>
      <ul>
        <?php foreach ($ctx['erros'] as $erro): ?>
          <li><?= e($erro) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Datas e hóspedes -->
  <form class="form-datas" method="get" action="<?= url('reservar.php') ?>">
    <input type="hidden" name="hotel" value="<?= (int) $hotel['id'] ?>">
    <?php if ($ctx['tipoId']): ?>
      <input type="hidden" name="tipo" value="<?= (int) $ctx['tipoId'] ?>">
    <?php endif; ?>

    <div class="campo">
      <label for="checkin">Check-in</label>
      <input type="date" id="checkin" name="checkin" min="<?= $hoje ?>" value="<?= e($periodo['checkin']) ?>" required>
    </div>

    <div class="campo">
      <label for="checkout">Check-out</label>
      <input type="date" id="checkout" name="checkout" min="<?= $hoje ?>" value="<?= e($periodo['checkout']) ?>" required>
    </div>

    <div class="campo">
      <label for="adultos">Adultos</label>
      <select id="adultos" name="adultos">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <option value="<?= $i ?>"<?= $i === $periodo['adultos'] ? ' selected' : '' ?>><?= $i ?></option>

        <?php endfor; ?>
      </select>
    </div>

    <div class="campo">
      <label for="criancas">Crianças</label>
      <select id="criancas" name="criancas">
        <?php for ($i = 0; $i <= 4; $i++): ?>
          <option value="<?= $i ?>"<?= $i === $periodo['criancas'] ? ' selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <button type="submit" class="btn btn--destaque">Ver disponibilidade</button>
  </form>

  <?php if ($ctx['periodoOk']): ?>
    <?php
      $hospedes = $periodo['adultos'] + $periodo['criancas'];
      $tipo     = $ctx['tipo'];
    ?>

    <p class="resumo-estadia">
      <?= plural($ctx['noites'], 'noite', 'noites') ?>
      &middot; <?= data_br($periodo['checkin']) ?> a <?= data_br($periodo['checkout']) ?>
      &middot; <?= plural($periodo['adultos'], 'adulto', 'adultos') ?><?php if ($periodo['criancas'] > 0): ?>, <?= plural($periodo['criancas'], 'criança', 'crianças') ?><?php endif; ?>
    </p>

    <?php if ($tipo && !tipoReservavel($tipo)): ?>
      <div class="alerta alerta--erro" role="alert">
        <?php if (!$tipo['cabe']): ?>
          O quarto <?= e($tipo['nome']) ?> comporta até <?= (int) $tipo['capacidade'] ?> hóspedes, e você informou <?= $hospedes ?>.

        <?php else: ?>
          O quarto <?= e($tipo['nome']) ?> não está disponível nessas datas. Veja as outras opções abaixo.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <h2>Quartos</h2>

    <?php if (empty($ctx['tipos'])): ?>
      <p>Este hotel ainda não tem quartos disponíveis para reserva.</p>
    <?php else: ?>
      <div class="lista-quartos">
        <?php foreach ($ctx['tipos'] as $t): ?>
          <?php $reservavel = tipoReservavel($t); ?>
          <article class="quarto<?= $reservavel ? '' : ' quarto--indisponivel' ?>">
            <div class="quarto__info">
              <h3><?= e($t['nome']) ?></h3>
              <?php if (!empty($t['descricao'])): ?>
                <p><?= e($t['descricao']) ?></p>
              <?php endif; ?>
              <p class="quarto__capacidade">Até <?= (int) $t['capacidade'] ?> hóspedes</p>

              <?php if (!$t['cabe']): ?>
                <p class="quarto__aviso">Não comporta <?= $hospedes ?> hóspedes.</p>
              <?php elseif ($t['disponiveis'] <= 0): ?>
                <p class="quarto__aviso">Esgotado para essas datas.</p>
              <?php elseif ($t['disponiveis'] <= 3): ?>
                <p class="quarto__aviso quarto__aviso--ok">Restam apenas <?= plural($t['disponiveis'], 'quarto', 'quartos') ?>.</p>
              <?php endif; ?>
            </div>

            <div class="quarto__reserva">

              <p class="preco"><strong><?= moeda($t['total']) ?></strong> por <?= plural($ctx['noites'], 'noite', 'noites') ?></p>
              <p class="preco"><?= moeda($t['preco_diaria']) ?> / noite</p>

              <?php if ($reservavel): ?>
                <a class="btn btn--destaque" href="<?= e(urlReserva('dados.php', $ctx, ['tipo' => (int) $t['id']])) ?>">Reservar</a>
              <?php else: ?>
                <span class="btn btn--contorno" aria-disabled="true">Indisponível</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif (!$ctx['buscou']): ?>
    <p class="subtitulo">Escolha as datas e o número de hóspedes para ver os quartos disponíveis.</p>
  <?php endif; ?>

  <p><a href="<?= url('hoteis.php') ?>">&larr; Voltar para os hotéis</a></p>
</section>

<?php
require __DIR__ . '/../includes/footer.php';