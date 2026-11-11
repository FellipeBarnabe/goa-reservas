<?php
// Cabeçalho do painel. Cada página define $titulo e $paginaAtual e já chamou exigirLogin().
$titulo      = $titulo ?? 'Painel';
$paginaAtual = $paginaAtual ?? '';

// Itens do menu do painel: chave => [texto, página]. A 4B inclui "Reservas".
// Itens do menu do painel: chave => [texto, página]
// Itens do menu do painel: chave => [texto, página]
$menuAdmin = [
    'painel'   => ['Painel', 'admin/'],
    'reservas' => ['Reservas', 'admin/reservas.php'],
    'hoteis'   => ['Hotéis', 'admin/hoteis.php'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titulo) ?> | Painel <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="admin">
  <header class="topo">
    <div class="container topo__linha">
      <a class="marca" href="<?= url('admin/') ?>">GOA <span>Painel</span></a>

      <button class="menu-botao" type="button" aria-expanded="false" aria-controls="menu">Menu</button>

      <nav class="menu" id="menu" aria-label="Painel">
        <?php foreach ($menuAdmin as $chave => [$texto, $pagina]): ?>
          <a href="<?= url($pagina) ?>"<?= $chave === $paginaAtual ? ' class="ativo" aria-current="page"' : '' ?>><?= e($texto) ?></a>

        <?php endforeach; ?>
        <a href="<?= url() ?>">Ver site</a>
        <form class="form-sair" method="post" action="<?= url('admin/logout.php') ?>">
          <?= csrfCampo() ?>
          <button type="submit" class="btn btn--contorno">Sair</button>
        </form>
      </nav>
    </div>
  </header>

  <main id="conteudo">