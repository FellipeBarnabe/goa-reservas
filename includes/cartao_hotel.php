<?php
// Cartão de um hotel. Espera a variável $hotel (linha da tabela hoteis + preco_minimo).
// Usado na home e na lista de hotéis: escrito uma vez, reaproveitado nos dois lugares.
$link = url('hotel.php?slug=' . urlencode($hotel['slug']));
?>
<article class="cartao-hotel">
  <a class="cartao-hotel__imagem" href="<?= $link ?>" aria-label="Ver <?= e($hotel['nome']) ?>">
    <?php if (!empty($hotel['imagem'])): ?>
      <img src="<?= e(url($hotel['imagem'])) ?>" alt="<?= e($hotel['nome']) ?>" loading="lazy">
    <?php else: ?>
      <span class="placeholder"><?= e($hotel['bandeira']) ?></span>
    <?php endif; ?>
  </a>

  <div class="cartao-hotel__corpo">
    <span class="etiqueta"><?= e($hotel['bandeira']) ?></span>
    <h3><a href="<?= $link ?>"><?= e($hotel['nome']) ?></a></h3>
    <p class="local"><?= e($hotel['cidade']) ?> &middot; <?= e($hotel['uf']) ?></p>

    <?php if ($hotel['preco_minimo'] !== null): ?>
      <p class="preco">A partir de <strong><?= moeda($hotel['preco_minimo']) ?></strong> / noite</p>
    <?php endif; ?>

    <a class="btn btn--contorno" href="<?= $link ?>">Ver hotel</a>
  </div>
</article>
