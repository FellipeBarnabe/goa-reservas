<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_hoteis.php';

exigirLogin();

// Ações da lista (mostrar, ocultar, excluir): sempre POST com token, e voltam para a lista (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
    $id   = inteiroDe($_POST, 'id', 0);

    $resultado = match ($acao) {
        'mostrar' => definirHotelAtivo($pdo, $id, 1),
        'ocultar' => definirHotelAtivo($pdo, $id, 0),
        'excluir' => excluirHotel($pdo, $id),
        default   => ['ok' => false, 'msg' => 'Ação inválida.'],
    };
    definirAviso($resultado['ok'] ? 'sucesso' : 'erro', $resultado['msg']);
    redirecionar('admin/hoteis.php');
}

$hoteis = listarHoteisAdmin($pdo);
$aviso  = pegarAviso();

$titulo      = 'Hotéis';
$paginaAtual = 'hoteis';
require __DIR__ . '/../../includes/admin_header.php';
?>


<section class="container secao">
  <div class="cabecalho-pagina">
    <h1>Hotéis</h1>
    <a class="btn btn--destaque" href="<?= url('admin/hotel_form.php') ?>">Novo hotel</a>
  </div>

  <?php if ($aviso): ?>
    <div class="alerta alerta--<?= $aviso['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status"><?= e($aviso['texto']) ?></div>
  <?php endif; ?>

  <?php if (empty($hoteis)): ?>
    <p>Nenhum hotel cadastrado.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr>
            <th>Hotel</th>
            <th>Local</th>
            <th>Quartos</th>
            <th>Reservas</th>
            <th>No site</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hoteis as $h): ?>
            <tr>
              <td><strong><?= e($h['nome']) ?></strong><br><small><?= e($h['bandeira']) ?></small></td>
              <td><?= e($h['cidade']) ?>/<?= e($h['uf']) ?></td>
              <td><?= (int) $h['tipos'] ?></td>

              <td><?= (int) $h['reservas'] ?></td>
              <td>
                <?php if ($h['ativo']): ?>
                  <span class="status status--ativo">Visível</span>
                <?php else: ?>
                  <span class="status status--oculto">Oculto</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="acoes-linha">
                  <a class="btn btn--contorno" href="<?= url('admin/hotel_form.php?id=' . (int) $h['id']) ?>">Editar</a>

                  <form method="post" action="<?= url('admin/hoteis.php') ?>">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
                    <input type="hidden" name="acao" value="<?= $h['ativo'] ? 'ocultar' : 'mostrar' ?>">
                    <button type="submit" class="btn btn--contorno"><?= $h['ativo'] ? 'Ocultar' : 'Mostrar' ?></button>
                  </form>

                  <?php if ((int) $h['reservas'] === 0): ?>
                    <form method="post" action="<?= url('admin/hoteis.php') ?>"
                          onsubmit="return confirm('Excluir este hotel e os quartos dele? Essa ação não pode ser desfeita.');">
                      <?= csrfCampo() ?>
                      <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
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
    <p class="subtitulo" style="margin-top:1rem">Hotéis com reservas não podem ser excluídos: oculte-os do site.</p>
  <?php endif; ?>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';