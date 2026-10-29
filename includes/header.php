<?php
// Cada página define $titulo e $paginaAtual antes de chamar este arquivo
$titulo      = $titulo ?? APP_NAME;
$paginaAtual = $paginaAtual ?? '';

// Itens do menu: chave => [texto, página]. Novas páginas entram aqui.
$menu = [
    'hoteis' => ['Hotéis', 'hoteis.php'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
  <a class="pular" href="#conteudo">Ir para o conteúdo</a>

  <header class="topo">
    <div class="container topo__linha">
      <a class="marca" href="<?= url() ?>">GOA <span>Hospitality Group</span></a>

      <button class="menu-botao" type="button" aria-expanded="false" aria-controls="menu">Menu</button>

      <nav class="menu" id="menu" aria-label="Principal">
        <?php foreach ($menu as $chave => [$texto, $pagina]): ?>
          <a href="<?= url($pagina) ?>"<?= $chave === $paginaAtual ? ' class="ativo" aria-current="page"' : '' ?>><?= e($texto) ?></a>
        <?php endforeach; ?>
        <a class="btn btn--destaque" href="<?= url('reservar.php') ?>">Reservar</a>
      </nav>
    </div>
  </header>

  <main id="conteudo">
