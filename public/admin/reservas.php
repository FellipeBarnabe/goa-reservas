<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_reservas.php';

exigirLogin();

$f      = lerFiltros($_GET);
$lista  = listarReservas($pdo, $f);
$hoteis = $pdo->query('SELECT id, nome FROM hoteis ORDER BY nome')->fetchAll();

$temFiltro = $f['busca'] !== '' || $f['status'] !== '' || $f['hotel'] > 0 || $f['de'] !== '' || $f['ate'] !== '';

$titulo      = 'Reservas';
$paginaAtual = 'reservas';
require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">
  <h1>Reservas</h1>

  <form class="filtros" method="get" action="<?= url('admin/reservas.php') ?>">
    <div class="campo campo--busca">
      <label for="busca">Buscar</label>
      <input type="text" id="busca" name="busca" maxlength="60" placeholder="Código, nome ou e-mail"
             value="<?= e($f['busca']) ?>">
    </div>

    <div class="campo">
      <label for="status">Status</label>

      <select id="status" name="status">
        <option value="">Todos</option>
        <?php foreach (STATUS_RESERVA as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>"<?= $f['status'] === $chave ? ' selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label for="hotel">Hotel</label>
      <select id="hotel" name="hotel">
        <option value="">Todos</option>
        <?php foreach ($hoteis as $h): ?>
          <option value="<?= (int) $h['id'] ?>"<?= $f['hotel'] === (int) $h['id'] ? ' selected' : '' ?>><?= e($h['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label for="de">Check-in a partir de</label>
      <input type="date" id="de" name="de" value="<?= e($f['de']) ?>">
    </div>

    <div class="campo">
      <label for="ate">Check-in até</label>
      <input type="date" id="ate" name="ate" value="<?= e($f['ate']) ?>">
    </div>

    <div class="filtros__acoes">
      <button type="submit" class="btn btn--destaque">Filtrar</button>
      <?php if ($temFiltro): ?>
        <a class="btn btn--contorno" href="<?= url('admin/reservas.php') ?>">Limpar</a>

      <?php endif; ?>
    </div>
  </form>

  <p class="subtitulo"><?= plural($lista['total'], 'reserva encontrada', 'reservas encontradas') ?></p>

  <?php if (empty($lista['linhas'])): ?>
    <p>Nenhuma reserva com esses filtros.</p>
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
          <?php foreach ($lista['linhas'] as $r): ?>
            <tr>
              <td><a href="<?= url('admin/reserva.php?id=' . (int) $r['id']) ?>"><strong><?= e($r['codigo']) ?></strong></a></td>
              <td><?= e($r['hospede_nome']) ?></td>
              <td><?= e($r['hotel']) ?><br><small><?= e($r['quarto']) ?></small></td>
              <td><?= data_br($r['checkin']) ?> a <?= data_br($r['checkout']) ?><br><small><?= plural(contarNoites($r['checkin'], $r['checkout']), 'noite', 'noites') ?></small></td>
              <td><?= moeda($r['valor_total']) ?></td>
              <td><span class="status status--<?= e($r['status']) ?>"><?= e(rotuloStatus($r['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>

    <?php if ($lista['paginas'] > 1): ?>
      <nav class="paginacao" aria-label="Páginas">
        <?php if ($lista['pagina'] > 1): ?>
          <a class="btn btn--contorno" href="<?= e(urlListaReservas($f, ['pagina' => $lista['pagina'] - 1])) ?>">&larr; Anterior</a>
        <?php endif; ?>
        <span>Página <?= $lista['pagina'] ?> de <?= $lista['paginas'] ?></span>
        <?php if ($lista['pagina'] < $lista['paginas']): ?>
          <a class="btn btn--contorno" href="<?= e(urlListaReservas($f, ['pagina' => $lista['pagina'] + 1])) ?>">Próxima &rarr;</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';