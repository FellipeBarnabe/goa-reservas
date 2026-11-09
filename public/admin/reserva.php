<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_reservas.php';

exigirLogin();

$ehPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$id     = inteiroDe($ehPost ? $_POST : $_GET, 'id', 0);

// Troca de status: valida, grava e volta para a própria página (PRG)
if ($ehPost) {
    exigirCsrf();
    $novo = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
    $resultado = alterarStatus($pdo, $id, $novo);
    definirAviso($resultado['ok'] ? 'sucesso' : 'erro', $resultado['msg']);
    redirecionar('admin/reserva.php?id=' . $id);
}

$reserva = $id > 0 ? buscarReserva($pdo, $id) : null;
$aviso   = pegarAviso();

$titulo      = $reserva ? 'Reserva ' . $reserva['codigo'] : 'Reserva não encontrada';
$paginaAtual = 'reservas';

if (!$reserva) {
    http_response_code(404);
    require __DIR__ . '/../../includes/admin_header.php';
    ?>
    <section class="container secao">

      <h1>Reserva não encontrada</h1>
      <p><a href="<?= url('admin/reservas.php') ?>">&larr; Voltar às reservas</a></p>
    </section>
    <?php
    require __DIR__ . '/../../includes/admin_footer.php';
    exit;
}

$noites  = contarNoites($reserva['checkin'], $reserva['checkout']);
$proximos = TRANSICOES_STATUS[$reserva['status']] ?? [];

// Texto do botão de cada mudança
$rotuloAcao = [
    'confirmada' => 'Confirmar reserva',
    'concluida'  => 'Marcar como concluída',
    'cancelada'  => 'Cancelar reserva',
];

require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">
  <p><a href="<?= url('admin/reservas.php') ?>">&larr; Voltar às reservas</a></p>

  <?php if ($aviso): ?>
    <div class="alerta alerta--<?= $aviso['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status"><?= e($aviso['texto']) ?></div>
  <?php endif; ?>

  <h1>Reserva <?= e($reserva['codigo']) ?></h1>
  <p><span class="status status--<?= e($reserva['status']) ?>"><?= e(rotuloStatus($reserva['status'])) ?></span></p>

  <div class="cartao">

    <h2>Estadia</h2>
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
  </div>

  <div class="cartao cartao--reserva">
    <h2>Hóspede</h2>
    <dl class="detalhes">
      <dt>Nome</dt>
      <dd><?= e($reserva['hospede_nome']) ?></dd>
      <dt>E-mail</dt>
      <dd><a href="mailto:<?= e($reserva['hospede_email']) ?>"><?= e($reserva['hospede_email']) ?></a></dd>
      <dt>Telefone</dt>
      <dd><?= e($reserva['hospede_telefone']) ?></dd>
      <dt>Observações</dt>
      <dd><?= trim((string) $reserva['observacoes']) !== '' ? nl2br(e($reserva['observacoes'])) : '&mdash;' ?></dd>
      <dt>Reservado em</dt>
      <dd><?= e(date('d/m/Y H:i', strtotime($reserva['created_at']))) ?></dd>
    </dl>
  </div>


  <div class="cartao cartao--reserva">
    <h2>Status</h2>
    <?php if (empty($proximos)): ?>
      <p>Esta reserva está finalizada: o status não pode mais ser alterado.</p>
    <?php else: ?>
      <div class="acoes-status">
        <?php foreach ($proximos as $destino): ?>
          <form method="post" action="<?= url('admin/reserva.php') ?>"
                <?php if ($destino === 'cancelada'): ?>onsubmit="return confirm('Cancelar esta reserva? Essa ação não pode ser desfeita.');"<?php endif; ?>>
            <?= csrfCampo() ?>
            <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
            <input type="hidden" name="status" value="<?= e($destino) ?>">
            <button type="submit" class="btn <?= $destino === 'cancelada' ? 'btn--perigo' : 'btn--destaque' ?>"><?= e($rotuloAcao[$destino]) ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="subtitulo" style="margin-top:1rem">
      <a href="<?= url('confirmacao.php?codigo=' . urlencode($reserva['codigo'])) ?>" target="_blank" rel="noopener">Ver a página de confirmação do hóspede</a>
    </p>
  </div>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';