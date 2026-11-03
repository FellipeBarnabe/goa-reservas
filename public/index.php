<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/funcoes.php';

// Hotéis ativos, com o menor preço de diária entre os quartos de cada um
$hoteis = $pdo->query(
    'SELECT h.id, h.nome, h.slug, h.bandeira, h.cidade, h.uf, h.imagem,
            (SELECT MIN(t.preco_diaria) FROM tipos_quarto t
              WHERE t.hotel_id = h.id AND t.ativo = 1) AS preco_minimo
       FROM hoteis h
      WHERE h.ativo = 1
      ORDER BY h.nome'
)->fetchAll();

$hoje = date('Y-m-d');

$titulo      = 'Início';
$paginaAtual = 'inicio';
require __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container">
    <p class="hero__etiqueta">Curadoria &middot; <?= date('Y') ?></p>
    <h1>Hospitalidade pensada em cada detalhe.</h1>
    <p class="hero__texto">Hotéis escolhidos com cuidado, do Nordeste ao Sul do Brasil. Reserve direto, sem intermediários.</p>
  </div>
</section>

<div class="container">
  <!-- Busca: envia os dados por GET para a página de reserva (Etapa 3) -->
  <form class="busca" action="<?= url('reservar.php') ?>" method="get">

    <div class="campo">
      <label for="hotel">Destino</label>
      <select id="hotel" name="hotel" required>
        <option value="">Escolha o seu destino</option>
        <?php foreach ($hoteis as $hotel): ?>
          <option value="<?= (int) $hotel['id'] ?>"><?= e($hotel['nome']) ?> &middot; <?= e($hotel['cidade']) ?>/<?= e($hotel['uf']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label for="checkin">Check-in</label>
      <input type="date" id="checkin" name="checkin" min="<?= $hoje ?>" required>
    </div>

    <div class="campo">
      <label for="checkout">Check-out</label>
      <input type="date" id="checkout" name="checkout" min="<?= $hoje ?>" required>
    </div>

    <div class="campo campo--curto">
      <label for="adultos">Adultos</label>
      <select id="adultos" name="adultos">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <option value="<?= $i ?>"<?= $i === 2 ? ' selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="campo campo--curto">
      <label for="criancas">Crianças</label>
      <select id="criancas" name="criancas">

        <?php for ($i = 0; $i <= 4; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <button type="submit" class="btn btn--destaque">Verificar</button>
  </form>
</div>

<section class="container secao">
  <h2>Nossos hotéis</h2>

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