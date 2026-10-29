<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';

$hoteis = $pdo->query(
    'SELECT id, nome, cidade, uf, bandeira FROM hoteis WHERE ativo = 1 ORDER BY nome'
)->fetchAll();

$titulo      = 'Início';
$paginaAtual = 'inicio';
require __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container">
    <p class="hero__etiqueta">Curadoria &middot; <?= date('Y') ?></p>
    <h1>Hospitalidade pensada em cada detalhe.</h1>
    <p class="hero__texto">Hotéis escolhidos com cuidado, do Nordeste ao Sul do Brasil.</p>
  </div>
</section>

<section class="container secao">
  <!-- Bloco provisório: só prova que o banco está conectado. Será substituído na Etapa 2. -->
  <div class="cartao">
    <h2>Fundação funcionando</h2>
    <p><?= count($hoteis) ?> hotel(is) encontrado(s) no banco de dados:</p>
    <ul>
      <?php foreach ($hoteis as $hotel): ?>
        <li><?= e($hotel['nome']) ?> &middot; <?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?> &middot; <?= e($hotel['bandeira']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php
require __DIR__ . '/../includes/footer.php';
