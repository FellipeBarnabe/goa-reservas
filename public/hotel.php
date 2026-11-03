<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';

// O slug vem da URL (?slug=goa-essence-hai). Confere que é texto antes de usar.
$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';

$stmt = $pdo->prepare(
    'SELECT id, nome, slug, bandeira, cidade, uf, descricao, imagem
       FROM hoteis
      WHERE slug = :slug AND ativo = 1'
);
$stmt->execute(['slug' => $slug]);
$hotel = $stmt->fetch();

$paginaAtual = 'hoteis';

// Hotel inexistente: responde 404 e mostra uma página amigável
if (!$hotel) {
    http_response_code(404);
    $titulo = 'Hotel não encontrado';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="container secao">
      <h1>Hotel não encontrado</h1>
      <p>Não encontramos o hotel que você procurou.</p>
      <a class="btn btn--destaque" href="<?= url('hoteis.php') ?>">Ver todos os hotéis</a>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}


// Tipos de quarto do hotel, do mais barato ao mais caro
$stmt = $pdo->prepare(
    'SELECT id, nome, descricao, capacidade, preco_diaria, imagem
       FROM tipos_quarto
      WHERE hotel_id = :hotel_id AND ativo = 1
      ORDER BY preco_diaria'
);
$stmt->execute(['hotel_id' => $hotel['id']]);
$quartos = $stmt->fetchAll();

$titulo = $hotel['nome'];
require __DIR__ . '/../includes/header.php';
?>

<section class="hero hero--hotel">
  <div class="container">
    <p class="hero__etiqueta"><?= e($hotel['bandeira']) ?> &middot; <?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?></p>
    <h1><?= e($hotel['nome']) ?></h1>
    <?php if (!empty($hotel['descricao'])): ?>
      <p class="hero__texto"><?= e($hotel['descricao']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="container secao">
  <h2>Quartos</h2>

  <?php if (empty($quartos)): ?>
    <p>Este hotel ainda não tem quartos disponíveis para reserva.</p>
  <?php else: ?>
    <div class="lista-quartos">

      <?php foreach ($quartos as $quarto): ?>
        <article class="quarto">
          <div class="quarto__info">
            <h3><?= e($quarto['nome']) ?></h3>
            <?php if (!empty($quarto['descricao'])): ?>
              <p><?= e($quarto['descricao']) ?></p>
            <?php endif; ?>
            <p class="quarto__capacidade">Até <?= (int) $quarto['capacidade'] ?> hóspedes</p>
          </div>

          <div class="quarto__reserva">
            <p class="preco"><strong><?= moeda($quarto['preco_diaria']) ?></strong> / noite</p>
            <a class="btn btn--destaque"
               href="<?= url('reservar.php?hotel=' . (int) $hotel['id'] . '&tipo=' . (int) $quarto['id']) ?>">Reservar</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p><a href="<?= url('hoteis.php') ?>">&larr; Voltar para os hotéis</a></p>
</section>

<?php
require __DIR__ . '/../includes/footer.php';