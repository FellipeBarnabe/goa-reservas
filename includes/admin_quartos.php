<?php
// Funções do cadastro de tipos de quarto no painel.

// Lê o formulário de quarto (de $_POST). Os números ficam como texto até a validação.
function lerQuartoForm(array $in): array
{
    $texto = fn(string $campo) => is_string($in[$campo] ?? null) ? trim($in[$campo]) : '';

    return [
        'nome'       => trim((string) preg_replace('/\s+/u', ' ', $texto('nome'))),
        'descricao'  => $texto('descricao'),
        'capacidade' => $texto('capacidade'),
        'preco'      => $texto('preco'),
        'quantidade' => $texto('quantidade'),
        'ativo'      => isset($in['ativo']) ? 1 : 0,
    ];
}

// "R$ 1.250,50" / "1250,5" / "1250.50" -> "1250.50". Devolve null se não for um valor válido.
function normalizarPreco(string $texto): ?string
{
    $t = str_replace(['R$', ' '], '', $texto);
    if (str_contains($t, ',')) {
        $t = str_replace('.', '', $t);   // ponto era separador de milhar
        $t = str_replace(',', '.', $t);  // vírgula era o decimal
    }
    if (!preg_match('/^\d{1,5}(\.\d{1,2})?$/', $t)) {
        return null;
    }
    $valor = (float) $t;
    return ($valor > 0) ? number_format($valor, 2, '.', '') : null;
}

// Devolve a lista de erros (vazia = tudo certo)
function validarQuarto(array $q): array
{
    $erros = [];

    $n = mb_strlen($q['nome']);
    if ($n < 2 || $n > 100) {
        $erros[] = 'O nome deve ter entre 2 e 100 caracteres.';
    }
    if (mb_strlen($q['descricao']) > 1000) {
        $erros[] = 'A descrição pode ter no máximo 1000 caracteres.';
    }
    if (!ctype_digit($q['capacidade']) || (int) $q['capacidade'] < 1 || (int) $q['capacidade'] > 10) {
        $erros[] = 'A capacidade deve ser um número de 1 a 10 hóspedes.';
    }
    if (normalizarPreco($q['preco']) === null) {
        $erros[] = 'Informe um preço de diária válido, maior que zero (ex.: 320,00).';
    }
    if (!ctype_digit($q['quantidade']) || (int) $q['quantidade'] < 1 || (int) $q['quantidade'] > 500) {
        $erros[] = 'A quantidade de quartos deve ser um número de 1 a 500.';
    }
    return $erros;
}

// Tipos de quarto de um hotel (ativos e ocultos), com a contagem de reservas de cada um
function listarQuartosAdmin(PDO $pdo, int $hotelId): array
{
    $stmt = $pdo->prepare(
        'SELECT t.id, t.nome, t.capacidade, t.preco_diaria, t.quantidade, t.ativo,
                (SELECT COUNT(*) FROM reservas r WHERE r.tipo_quarto_id = t.id) AS reservas
           FROM tipos_quarto t
          WHERE t.hotel_id = :hotel
          ORDER BY t.preco_diaria, t.id'
    );
    $stmt->execute(['hotel' => $hotelId]);
    return $stmt->fetchAll();
}

function buscarQuarto(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM tipos_quarto WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// Cria o tipo de quarto. Devolve ['ok' => bool, 'msg' => texto].
function criarQuarto(PDO $pdo, int $hotelId, array $q): array
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO tipos_quarto (hotel_id, nome, descricao, capacidade, preco_diaria, quantidade, ativo)
             VALUES (:hotel, :nome, :descricao, :capacidade, :preco, :quantidade, :ativo)'
        );
        $stmt->execute([
            'hotel'      => $hotelId,
            'nome'       => $q['nome'],
            'descricao'  => $q['descricao'] !== '' ? $q['descricao'] : null,
            'capacidade' => (int) $q['capacidade'],
            'preco'      => normalizarPreco($q['preco']),
            'quantidade' => (int) $q['quantidade'],
            'ativo'      => $q['ativo'],
        ]);
    } catch (PDOException $erro) {
        return ['ok' => false, 'msg' => 'Não foi possível salvar. Tente novamente.'];
    }
    return ['ok' => true, 'msg' => 'Tipo de quarto cadastrado.'];
}

// Maior número de reservas ativas (pendente/confirmada) ao mesmo tempo, de hoje em diante.
// A quantidade de quartos não pode ficar abaixo disso, senão haveria overbooking.
function quantidadeMinima(PDO $pdo, int $tipoId): int
{
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT COALESCE(MAX(x.c), 0) FROM (
            SELECT (SELECT COUNT(*) FROM reservas r2
                     WHERE r2.tipo_quarto_id = r1.tipo_quarto_id
                       AND r2.status IN ('pendente', 'confirmada')
                       AND r2.checkin  <= GREATEST(r1.checkin, :hoje1)
                       AND r2.checkout >  GREATEST(r1.checkin, :hoje2)) AS c
              FROM reservas r1
             WHERE r1.tipo_quarto_id = :id
               AND r1.status IN ('pendente', 'confirmada')
               AND r1.checkout > :hoje3
         ) x"
    );
    $stmt->execute(['hoje1' => $hoje, 'hoje2' => $hoje, 'hoje3' => $hoje, 'id' => $tipoId]);
    return (int) $stmt->fetchColumn();
}

// Atualiza o tipo de quarto. As reservas já feitas guardam o valor que foi cobrado,
// então mudar o preço só vale para as próximas.
function atualizarQuarto(PDO $pdo, int $id, array $q): array
{
    $pdo->beginTransaction();
    try {
        // Trava a linha, como faz a criação de reservas: ninguém reserva enquanto conferimos
        $stmt = $pdo->prepare('SELECT id FROM tipos_quarto WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Tipo de quarto não encontrado.'];
        }

        $minimo = quantidadeMinima($pdo, $id);
        if ((int) $q['quantidade'] < $minimo) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Há ' . plural($minimo, 'reserva ativa', 'reservas ativas')
                . ' ao mesmo tempo em alguma data: a quantidade não pode ser menor que ' . $minimo . '.'];
        }

        $stmt = $pdo->prepare(
            'UPDATE tipos_quarto
                SET nome = :nome, descricao = :descricao, capacidade = :capacidade,
                    preco_diaria = :preco, quantidade = :quantidade, ativo = :ativo
              WHERE id = :id'
        );
        $stmt->execute([
            'nome'       => $q['nome'],
            'descricao'  => $q['descricao'] !== '' ? $q['descricao'] : null,
            'capacidade' => (int) $q['capacidade'],
            'preco'      => normalizarPreco($q['preco']),
            'quantidade' => (int) $q['quantidade'],
            'ativo'      => $q['ativo'],
            'id'         => $id,
        ]);
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => 'Não foi possível salvar. Tente novamente.'];
    }
    return ['ok' => true, 'msg' => 'Tipo de quarto atualizado.'];
}

// Mostra (1) ou oculta (0) o tipo no site. O $hotelId impede mexer em quarto de outro hotel.
function definirQuartoAtivo(PDO $pdo, int $hotelId, int $id, int $ativo): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tipos_quarto WHERE id = :id AND hotel_id = :hotel');
    $stmt->execute(['id' => $id, 'hotel' => $hotelId]);
    if ((int) $stmt->fetchColumn() === 0) {
        return ['ok' => false, 'msg' => 'Tipo de quarto não encontrado.'];
    }

    $stmt = $pdo->prepare('UPDATE tipos_quarto SET ativo = :ativo WHERE id = :id AND hotel_id = :hotel');
    $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id, 'hotel' => $hotelId]);
    return ['ok' => true, 'msg' => $ativo ? 'Quarto agora aparece no site.' : 'Quarto ocultado do site.'];
}

// Exclui o tipo de quarto só se nenhuma reserva (de qualquer status) estiver ligada a ele.
function excluirQuarto(PDO $pdo, int $hotelId, int $id): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM tipos_quarto WHERE id = :id AND hotel_id = :hotel FOR UPDATE');
        $stmt->execute(['id' => $id, 'hotel' => $hotelId]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Tipo de quarto não encontrado.'];
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE tipo_quarto_id = :id');
        $stmt->execute(['id' => $id]);
        $reservas = (int) $stmt->fetchColumn();
        if ($reservas > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Este quarto tem ' . plural($reservas, 'reserva', 'reservas')
                . ' e não pode ser excluído. Oculte-o do site em vez disso.'];
        }

        $stmt = $pdo->prepare('DELETE FROM tipos_quarto WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => 'Não foi possível excluir. O quarto pode ter reservas.'];
    }
    return ['ok' => true, 'msg' => 'Tipo de quarto excluído.'];
}