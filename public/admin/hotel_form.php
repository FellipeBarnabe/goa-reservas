<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/reservas.php';
require __DIR__ . '/../../includes/admin_hoteis.php';

exigirLogin();

// Sem ?id é um hotel novo. Com ?id é edição (o id fica no endereço também no POST).
$editando = isset($_GET['id']);
$id       = $editando ? inteiroDe($_GET, 'id', 0) : 0;
$hotel    = $editando && $id > 0 ? buscarHotel($pdo, $id) : null;

if ($editando && !$hotel) {
    http_response_code(404);
    $titulo      = 'Hotel não encontrado';
    $paginaAtual = 'hoteis';
    require __DIR__ . '/../../includes/admin_header.php';
    ?>
    <section class="container secao">
      <h1>Hotel não encontrado</h1>
      <p><a href="<?= url('admin/hoteis.php') ?>">&larr; Voltar aos hotéis</a></p>
    </section>
    <?php
    require __DIR__ . '/../../includes/admin_footer.php';
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();
    $dados = lerHotelForm($_POST);
    $erros = validarHotel($dados);

    if (empty($erros)) {
        $resultado = $editando ? atualizarHotel($pdo, $id, $dados) : criarHotel($pdo, $dados);
        if ($resultado['ok']) {
            definirAviso('sucesso', $resultado['msg']);
            redirecionar('admin/hoteis.php');
        }
        $erros[] = $resultado['msg'];
    }
} elseif ($editando) {
    $dados = [
        'nome'      => $hotel['nome'],
        'bandeira'  => $hotel['bandeira'],
        'cidade'    => $hotel['cidade'],
        'uf'        => $hotel['uf'],
        'descricao' => (string) $hotel['descricao'],
        'ativo'     => (int) $hotel['ativo'],
    ];
} else {
    $dados = ['nome' => '', 'bandeira' => '', 'cidade' => '', 'uf' => '', 'descricao' => '', 'ativo' => 1];
}

$titulo      = $editando ? 'Editar hotel' : 'Novo hotel';
$paginaAtual = 'hoteis';
$acao        = url('admin/hotel_form.php') . ($editando ? '?id=' . $id : '');
require __DIR__ . '/../../includes/admin_header.php';
?>

<section class="container secao">

  <p><a href="<?= url('admin/hoteis.php') ?>">&larr; Voltar aos hotéis</a></p>
  <h1><?= e($titulo) ?></h1>

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
          <label for="nome">Nome do hotel</label>
          <input type="text" id="nome" name="nome" maxlength="120" required autofocus
                 value="<?= e($dados['nome']) ?>">
        </div>

        <div class="campo">
          <label for="bandeira">Bandeira</label>
          <select id="bandeira" name="bandeira" required>
            <option value="">Escolha...</option>
            <?php foreach (BANDEIRAS as $b): ?>
              <option value="<?= e($b) ?>"<?= $dados['bandeira'] === $b ? ' selected' : '' ?>><?= e($b) ?></option>
            <?php endforeach; ?>

          </select>
        </div>

        <div class="campo">
          <label for="cidade">Cidade</label>
          <input type="text" id="cidade" name="cidade" maxlength="80" required
                 value="<?= e($dados['cidade']) ?>">
        </div>

        <div class="campo">
          <label for="uf">Estado (UF)</label>
          <select id="uf" name="uf" required>
            <option value="">Escolha...</option>
            <?php foreach (UFS as $uf): ?>
              <option value="<?= e($uf) ?>"<?= $dados['uf'] === $uf ? ' selected' : '' ?>><?= e($uf) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="campo campo--cheio">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" rows="5" maxlength="2000"><?= e($dados['descricao']) ?></textarea>
        </div>

        <div class="campo campo--cheio">
          <label class="check">
            <input type="checkbox" name="ativo" value="1"<?= $dados['ativo'] ? ' checked' : '' ?>>
            Exibir este hotel no site
          </label>
        </div>
      </div>


      <?php if ($editando): ?>
        <p class="subtitulo">Endereço no site: <code>hotel.php?slug=<?= e($hotel['slug']) ?></code> (não muda ao editar o nome).</p>
      <?php endif; ?>

      <div class="form-acoes">
        <button type="submit" class="btn btn--destaque">Salvar</button>
        <a class="btn btn--contorno" href="<?= url('admin/hoteis.php') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</section>

<?php
require __DIR__ . '/../../includes/admin_footer.php';