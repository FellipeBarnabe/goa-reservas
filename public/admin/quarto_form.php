<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_hoteis.php';
require __DIR__ . '/../../includes/admin_quartos.php';

exigirLogin();

// Quarto novo: ?hotel=ID (de qual hotel). Edição: ?id=ID (o hotel vem do próprio quarto).
$editando = isset($_GET['id']);
$quarto   = null;

if ($editando) {
    $id     = inteiroDe($_GET, 'id', 0);
    $quarto = $id > 0 ? buscarQuarto($pdo, $id) : null;
    $hotel  = $quarto ? buscarHotel($pdo, (int) $quarto['hotel_id']) : null;
} else {
    $id      = 0;
    $hotelId = inteiroDe($_GET, 'hotel', 0);
    $hotel   = $hotelId > 0 ? buscarHotel($pdo, $hotelId) : null;
}

if (!$hotel) {
    http_response_code(404);
    $titulo      = 'Não encontrado';
    $paginaAtual = 'hoteis';
    require __DIR__ . '/../../includes/admin_header.php';
    ?>
    <section class="container secao">
      <h1>Não encontrado</h1>

      <p><a href="<?= url('admin/hoteis.php') ?>">&larr; Voltar aos hotéis</a></p>
    </section>
    <?php
    require __DIR__ . '/../../includes/admin_footer.php';
    exit;
}

$hotelId  = (int) $hotel['id'];
$urlLista = url('admin/quartos.php?hotel=' . $hotelId);
$erros    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $dados = lerQuartoForm($_POST);
    $erros = validarQuarto($dados);

    if (empty($erros)) {
        $resultado = $editando ? atualizarQuarto($pdo, $id, $dados) : criarQuarto($pdo, $hotelId, $dados);
        if ($resultado['ok']) {
            definirAviso('sucesso', $resultado['msg']);
            header('Location: ' . $urlLista);
            exit;
        }
        $erros[] = $resultado['msg'];
    }
} elseif ($editando) {
    $dados = [
        'nome'       => $quarto['nome'],
        'descricao'  => (string) $quarto['descricao'],
        'capacidade' => (string) $quarto['capacidade'],
        'preco'      => number_format((float) $quarto['preco_diaria'], 2, ',', ''),
        'quantidade' => (string) $quarto['quantidade'],

        'ativo'      => (int) $quarto['ativo'],
    ];
} else {
    $dados = ['nome' => '', 'descricao' => '', 'capacidade' => '2', 'preco' => '', 'quantidade' => '1', 'ativo' => 1];
}

$titulo      = $editando ? 'Editar quarto' : 'Novo tipo de quarto';
$paginaAtual = 'hoteis';
$acao        = url('admin/quarto_form.php') . ($editando ? '?id=' . $id : '?hotel=' . $hotelId);
require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">
  <p><a href="<?= e($urlLista) ?>">&larr; Voltar aos quartos</a></p>
  <h1><?= e($titulo) ?></h1>
  <p class="subtitulo"><?= e($hotel['nome']) ?></p>

  <?php if (!empty($erros)): ?>
    <div class="alerta alerta--erro" role="alert">
      <strong>Corrija os campos abaixo:</strong>
      <ul>
        <?php foreach ($erros as $erro): ?>
          <li><?= e($erro) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="cartao cartao--reserva">
    <form method="post" action="<?= e($acao) ?>">
      <?= csrfCampo() ?>


      <div class="grade-form">
        <div class="campo campo--cheio">
          <label for="nome">Nome do tipo de quarto</label>
          <input type="text" id="nome" name="nome" maxlength="100" required autofocus
                 placeholder="Ex.: Suíte Vista Mar" value="<?= e($dados['nome']) ?>">
        </div>

        <div class="campo">
          <label for="preco">Diária (R$)</label>
          <input type="text" id="preco" name="preco" inputmode="decimal" required
                 placeholder="320,00" value="<?= e($dados['preco']) ?>">
        </div>

        <div class="campo">
          <label for="capacidade">Capacidade (hóspedes)</label>
          <input type="number" id="capacidade" name="capacidade" min="1" max="10" required
                 value="<?= e($dados['capacidade']) ?>">
        </div>

        <div class="campo">
          <label for="quantidade">Quantos quartos deste tipo</label>
          <input type="number" id="quantidade" name="quantidade" min="1" max="500" required
                 value="<?= e($dados['quantidade']) ?>">
        </div>

        <div class="campo campo--cheio">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" rows="4" maxlength="1000"><?= e($dados['descricao']) ?></textarea>
        </div>

        <div class="campo campo--cheio">
          <label class="check">

            <input type="checkbox" name="ativo" value="1"<?= $dados['ativo'] ? ' checked' : '' ?>>
            Exibir este quarto no site
          </label>
        </div>
      </div>

      <?php if ($editando): ?>
        <p class="subtitulo">Mudar o preço vale só para as próximas reservas: as já feitas mantêm o valor cobrado.</p>
      <?php endif; ?>

      <div class="form-acoes">
        <button type="submit" class="btn btn--destaque">Salvar</button>
        <a class="btn btn--contorno" href="<?= e($urlLista) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';