<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_hoteis.php';
require __DIR__ . '/../../includes/admin_quartos.php';

exigirLogin();

// Os quartos são sempre vistos dentro de um hotel: ?hotel=ID
$hotelId = inteiroDe($_GET, 'hotel', 0);
$hotel   = $hotelId > 0 ? buscarHotel($pdo, $hotelId) : null;

if (!$hotel) {
    http_response_code(404);
    $titulo      = 'Hotel não encontrado';
    $paginaAtual = 'hoteis';
    require __DIR__ . '/../../includes/admin_header.php';
    ?>
    <section class="container secao">
      <h1>Hotel não encontrado</h1>
      <p><a href="<?= url('admin/hoteis.php') ?>">&larr; Voltar aos hotéis</a></p>
    </section>
    <?php
    require __DIR__ . '/../../includes/admin_footer.php';
    exit;
}

$urlLista = url('admin/quartos.php?hotel=' . $hotelId);

// Ações da lista (mostrar, ocultar, excluir): POST com token, e voltam para a lista (PRG)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
    $id   = inteiroDe($_POST, 'id', 0);

    $resultado = match ($acao) {
        'mostrar' => definirQuartoAtivo($pdo, $hotelId, $id, 1),
        'ocultar' => definirQuartoAtivo($pdo, $hotelId, $id, 0),
        'excluir' => excluirQuarto($pdo, $hotelId, $id),
        default   => ['ok' => false, 'msg' => 'Ação inválida.'],
    };
    definirAviso($resultado['ok'] ? 'sucesso' : 'erro', $resultado['msg']);
    header('Location: ' . $urlLista);
    exit;
}

$quartos = listarQuartosAdmin($pdo, $hotelId);
$aviso   = pegarAviso();

$titulo      = 'Quartos de ' . $hotel['nome'];
$paginaAtual = 'hoteis';
require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">
  <p><a href="<?= url('admin/hoteis.php') ?>">&larr; Voltar aos hotéis</a></p>

  <div class="cabecalho-pagina">
    <div>
      <h1>Quartos</h1>
      <p class="subtitulo"><?= e($hotel['nome']) ?> &middot; <?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?></p>
    </div>

    <a class="btn btn--destaque" href="<?= url('admin/quarto_form.php?hotel=' . $hotelId) ?>">Novo tipo de quarto</a>
  </div>

  <?php if ($aviso): ?>
    <div class="alerta alerta--<?= $aviso['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status"><?= e($aviso['texto']) ?></div>
  <?php endif; ?>

  <?php if (empty($quartos)): ?>
    <p>Este hotel ainda não tem tipos de quarto. Sem eles, o hóspede não consegue reservar.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr>
            <th>Tipo de quarto</th>
            <th>Diária</th>
            <th>Capacidade</th>
            <th>Quartos</th>
            <th>Reservas</th>
            <th>No site</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quartos as $q): ?>
            <tr>
              <td><strong><?= e($q['nome']) ?></strong></td>
              <td><?= moeda($q['preco_diaria']) ?></td>
              <td><?= plural((int) $q['capacidade'], 'hóspede', 'hóspedes') ?></td>
              <td><?= (int) $q['quantidade'] ?></td>
              <td><?= (int) $q['reservas'] ?></td>
              <td>

                <?php if ($q['ativo']): ?>
                  <span class="status status--ativo">Visível</span>
                <?php else: ?>
                  <span class="status status--oculto">Oculto</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="acoes-linha">
                  <a class="btn btn--contorno" href="<?= url('admin/quarto_form.php?id=' . (int) $q['id']) ?>">Editar</a>

                  <form method="post" action="<?= e($urlLista) ?>">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                    <input type="hidden" name="acao" value="<?= $q['ativo'] ? 'ocultar' : 'mostrar' ?>">
                    <button type="submit" class="btn btn--contorno"><?= $q['ativo'] ? 'Ocultar' : 'Mostrar' ?></button>
                  </form>

                  <?php if ((int) $q['reservas'] === 0): ?>
                    <form method="post" action="<?= e($urlLista) ?>"
                          onsubmit="return confirm('Excluir este tipo de quarto? Essa ação não pode ser desfeita.');">
                      <?= csrfCampo() ?>
                      <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                      <input type="hidden" name="acao" value="excluir">
                      <button type="submit" class="btn btn--perigo">Excluir</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div>
    <p class="subtitulo" style="margin-top:1rem">Quartos com reservas não podem ser excluídos: oculte-os do site.</p>
  <?php endif; ?>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';