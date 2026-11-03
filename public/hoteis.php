<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';

$hoteis = $pdo->query(
    'SELECT h.id, h.nome, h.slug, h.bandeira, h.cidade, h.uf, h.imagem,
            (SELECT MIN(t.preco_diaria) FROM tipos_quarto t
              WHERE t.hotel_id = h.id AND t.ativo = 1) AS preco_minimo
       FROM hoteis h
      WHERE h.ativo = 1
      ORDER BY h.nome'
)->fetchAll();

$titulo      = 'Hotéis';
$paginaAtual = 'hoteis';
require __DIR__ . '/../includes/header.php';
?>

<section class="container secao">
  <h1>Hotéis</h1>
  <p class="subtitulo">Escolha onde você quer ficar.</p>

  <?php if (empty($hoteis)): ?>
    <p>Nenhum hotel disponível no momento.</p>
  <?php else: ?>
    <div class="grade-cartoes">
      <?php foreach ($hoteis as $hotel): ?>
        <?php require __DIR__ . '/../includes/cartao_hotel.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>


<?php
require __DIR__ . '/../includes/footer.php';