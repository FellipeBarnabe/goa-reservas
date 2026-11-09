<?php
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';

// Quem já está logado não precisa ver o login
if (usuarioLogado()) {
    redirecionar('admin/');
}

$erro  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();

    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe o e-mail e a senha.';
    } elseif (fazerLogin($pdo, $email, $senha)) {
        redirecionar('admin/');
    } else {
        // Mensagem única: não diz se foi o e-mail ou a senha que errou
        $erro = 'E-mail ou senha incorretos.';
    }
}

$titulo = 'Entrar';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Entrar | Painel <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="admin admin--login">
  <main id="conteudo" class="login">
    <div class="cartao login__caixa">
      <p class="marca login__marca">GOA <span>Painel</span></p>
      <h1>Entrar</h1>

      <?php if ($erro !== ''): ?>
        <div class="alerta alerta--erro" role="alert"><?= e($erro) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('admin/login.php') ?>">
        <?= csrfCampo() ?>

        <div class="campo">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" maxlength="150" required autofocus
                 autocomplete="username" value="<?= e($email) ?>">
        </div>

        <div class="campo" style="margin-top:1rem">

          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" maxlength="200" required
                 autocomplete="current-password">
        </div>

        <div class="form-acoes">
          <button type="submit" class="btn btn--destaque">Entrar</button>
          <a class="btn btn--contorno" href="<?= url() ?>">Voltar ao site</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>