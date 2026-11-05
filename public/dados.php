<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';
require __DIR__ . '/../includes/reservas.php';

$ehPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$ctx    = carregarContextoReserva($pdo, $ehPost ? $_POST : $_GET);

if (!$ctx['hotel']) {
    redirecionar('index.php');
}

// Datas inválidas ou quarto indisponível: volta para a escolha de quartos
if (!$ctx['periodoOk'] || !tipoReservavel($ctx['tipo'])) {
    voltarParaQuartos($ctx);
}

$hotel   = $ctx['hotel'];
$tipo    = $ctx['tipo'];
$periodo = $ctx['periodo'];
$erros   = [];
$hospede = $ehPost ? lerHospede($_POST) : lerHospede([]);

if ($ehPost) {
    $erros = validarHospede($hospede);

    if (empty($erros)) {
        $resultado = criarReserva($pdo, [
            'hotel_id'    => (int) $hotel['id'],
            'tipo_id'     => (int) $tipo['id'],
            'checkin'     => $periodo['checkin'],
            'checkout'    => $periodo['checkout'],

            'adultos'     => $periodo['adultos'],
            'criancas'    => $periodo['criancas'],
            'nome'        => $hospede['nome'],
            'email'       => $hospede['email'],
            'telefone'    => $hospede['telefone'],
            'observacoes' => $hospede['observacoes'],
        ]);

        if ($resultado['ok']) {
            redirecionar('confirmacao.php?codigo=' . urlencode($resultado['codigo']));
        }

        // Não deu: alguém reservou o último quarto enquanto você preenchia.
        // Recarrega a disponibilidade e volta para a escolha de quartos.
        voltarParaQuartos(carregarContextoReserva($pdo, $_POST));
    }
}

$titulo      = 'Seus dados';
$paginaAtual = 'hoteis';
require __DIR__ . '/../includes/header.php';

// Campos que acompanham o formulário (hotel, quarto, datas e hóspedes)
$ocultos = [
    'hotel'    => (int) $hotel['id'],
    'tipo'     => (int) $tipo['id'],
    'checkin'  => $periodo['checkin'],
    'checkout' => $periodo['checkout'],
    'adultos'  => $periodo['adultos'],
    'criancas' => $periodo['criancas'],
];
?>


<section class="hero hero--hotel">
  <div class="container">
    <p class="hero__etiqueta">Reserva &middot; <?= e($hotel['bandeira']) ?></p>
    <h1>Seus dados</h1>
    <p class="hero__texto">Falta pouco para reservar no <?= e($hotel['nome']) ?>.</p>
  </div>
</section>

<section class="container secao">
  <div class="cartao">
    <h2>Resumo da reserva</h2>
    <dl class="detalhes">
      <dt>Hotel</dt>
      <dd><?= e($hotel['nome']) ?> &middot; <?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?></dd>
      <dt>Quarto</dt>
      <dd><?= e($tipo['nome']) ?></dd>
      <dt>Período</dt>
      <dd><?= data_br($periodo['checkin']) ?> a <?= data_br($periodo['checkout']) ?> (<?= plural($ctx['noites'], 'noite', 'noites') ?>)</dd>
      <dt>Hóspedes</dt>
      <dd><?= plural($periodo['adultos'], 'adulto', 'adultos') ?><?php if ($periodo['criancas'] > 0): ?>, <?= plural($periodo['criancas'], 'criança', 'crianças') ?><?php endif; ?></dd>
      <dt>Total</dt>
      <dd><strong><?= moeda($tipo['total']) ?></strong></dd>
    </dl>
    <a href="<?= e(urlReserva('reservar.php', $ctx)) ?>">&larr; Escolher outro quarto ou outras datas</a>
  </div>

  <?php if (!empty($erros)): ?>
    <div class="alerta alerta--erro" role="alert" style="margin-top:1.5rem">
      <strong>Corrija os campos abaixo:</strong>
      <ul>
        <?php foreach ($erros as $erro): ?>

          <li><?= e($erro) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="cartao cartao--reserva">
    <h2>Dados do hóspede</h2>

    <form method="post" action="<?= url('dados.php') ?>">
      <?php foreach ($ocultos as $nome => $valor): ?>
        <input type="hidden" name="<?= $nome ?>" value="<?= e((string) $valor) ?>">
      <?php endforeach; ?>

      <div class="grade-form">
        <div class="campo">
          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" maxlength="120" required autofocus
                 value="<?= e($hospede['nome']) ?>">
        </div>

        <div class="campo">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" maxlength="150" required
                 value="<?= e($hospede['email']) ?>">
        </div>

        <div class="campo">
          <label for="telefone">Telefone</label>
          <input type="text" id="telefone" name="telefone" maxlength="20" required
                 placeholder="(00) 00000-0000" value="<?= e($hospede['telefone']) ?>">
        </div>


        <div class="campo campo--cheio">
          <label for="observacoes">Observações (opcional)</label>
          <textarea id="observacoes" name="observacoes" rows="3" maxlength="500"><?= e($hospede['observacoes']) ?></textarea>
        </div>
      </div>

      <p class="subtitulo">Sua reserva ficará pendente até a confirmação do hotel.</p>

      <div class="form-acoes">
        <button type="submit" class="btn btn--destaque">Confirmar reserva</button>
        <a class="btn btn--contorno" href="<?= e(urlReserva('reservar.php', $ctx)) ?>">Voltar</a>
      </div>
    </form>
  </div>
</section>

<?php
require __DIR__ . '/../includes/footer.php';