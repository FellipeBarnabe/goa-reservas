<?php
// Funções do cadastro de hotéis no painel.

const BANDEIRAS = ['AURA', 'ESSENCE', 'RESORT', 'CITY', 'SMART'];

const UFS = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
    'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

// "GOA Essence João Pessoa" -> "goa-essence-joao-pessoa" (usado na URL do hotel)
function gerarSlug(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ]);
    $texto = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
    $texto = trim(substr($texto, 0, 120), '-');
    return $texto !== '' ? $texto : 'hotel';
}

// Se o slug já existe, acrescenta -2, -3...
function slugUnico(PDO $pdo, string $base): string
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hoteis WHERE slug = :slug');
    $slug = $base;

    for ($n = 2; $n < 1000; $n++) {
        $stmt->execute(['slug' => $slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $n;
    }
    return $base . '-' . bin2hex(random_bytes(3));
}

// Lê o formulário de hotel (de $_POST) já limpo
function lerHotelForm(array $in): array
{
    $texto = fn(string $campo) => is_string($in[$campo] ?? null)
        ? trim((string) preg_replace('/\s+/u', ' ', $in[$campo]))
        : '';

    return [
        'nome'      => $texto('nome'),
        'bandeira'  => $texto('bandeira'),
        'cidade'    => $texto('cidade'),
        'uf'        => strtoupper($texto('uf')),
        'descricao' => is_string($in['descricao'] ?? null) ? trim($in['descricao']) : '',
        'ativo'     => isset($in['ativo']) ? 1 : 0,
    ];
}

// Devolve a lista de erros (vazia = tudo certo)
function validarHotel(array $h): array
{
    $erros = [];
    $n = mb_strlen($h['nome']);

    if ($n < 3 || $n > 120) {
        $erros[] = 'O nome deve ter entre 3 e 120 caracteres.';
    }
    if (!in_array($h['bandeira'], BANDEIRAS, true)) {
        $erros[] = 'Escolha uma bandeira.';
    }
    $c = mb_strlen($h['cidade']);
    if ($c < 2 || $c > 80) {
        $erros[] = 'A cidade deve ter entre 2 e 80 caracteres.';
    }
    if (!in_array($h['uf'], UFS, true)) {
        $erros[] = 'Escolha um estado (UF) válido.';
    }
    if (mb_strlen($h['descricao']) > 2000) {
        $erros[] = 'A descrição pode ter no máximo 2000 caracteres.';
    }
    return $erros;
}

// Todos os hotéis (ativos e ocultos) com a contagem de quartos e de reservas
function listarHoteisAdmin(PDO $pdo): array
{
    return $pdo->query(
        'SELECT h.id, h.nome, h.slug, h.bandeira, h.cidade, h.uf, h.ativo,
                (SELECT COUNT(*) FROM tipos_quarto t WHERE t.hotel_id = h.id) AS tipos,
                (SELECT COUNT(*) FROM reservas r
                   JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
                  WHERE t.hotel_id = h.id) AS reservas
           FROM hoteis h
          ORDER BY h.nome'
    )->fetchAll();
}


function buscarHotel(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM hoteis WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// Cria o hotel. Devolve ['ok' => bool, 'msg' => texto].
function criarHotel(PDO $pdo, array $h): array
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO hoteis (nome, slug, bandeira, cidade, uf, descricao, ativo)
             VALUES (:nome, :slug, :bandeira, :cidade, :uf, :descricao, :ativo)'
        );
        $stmt->execute([
            'nome'      => $h['nome'],
            'slug'      => slugUnico($pdo, gerarSlug($h['nome'])),
            'bandeira'  => $h['bandeira'],
            'cidade'    => $h['cidade'],
            'uf'        => $h['uf'],
            'descricao' => $h['descricao'] !== '' ? $h['descricao'] : null,
            'ativo'     => $h['ativo'],
        ]);
    } catch (PDOException $erro) {
        return ['ok' => false, 'msg' => 'Não foi possível salvar. Tente novamente.'];
    }
    return ['ok' => true, 'msg' => 'Hotel cadastrado.'];
}

// Atualiza o hotel. O slug (endereço) não muda, para não quebrar links já divulgados.

function atualizarHotel(PDO $pdo, int $id, array $h): array
{
    try {
        $stmt = $pdo->prepare(
            'UPDATE hoteis
                SET nome = :nome, bandeira = :bandeira, cidade = :cidade, uf = :uf,
                    descricao = :descricao, ativo = :ativo
              WHERE id = :id'
        );
        $stmt->execute([
            'nome'      => $h['nome'],
            'bandeira'  => $h['bandeira'],
            'cidade'    => $h['cidade'],
            'uf'        => $h['uf'],
            'descricao' => $h['descricao'] !== '' ? $h['descricao'] : null,
            'ativo'     => $h['ativo'],
            'id'        => $id,
        ]);
    } catch (PDOException $erro) {
        return ['ok' => false, 'msg' => 'Não foi possível salvar. Tente novamente.'];
    }
    return ['ok' => true, 'msg' => 'Hotel atualizado.'];
}

// Mostra (1) ou oculta (0) o hotel no site. As reservas existentes não são afetadas.
function definirHotelAtivo(PDO $pdo, int $id, int $ativo): array
{
    $stmt = $pdo->prepare('UPDATE hoteis SET ativo = :ativo WHERE id = :id');
    $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);
    if (!buscarHotel($pdo, $id)) {
        return ['ok' => false, 'msg' => 'Hotel não encontrado.'];
    }

    return ['ok' => true, 'msg' => $ativo ? 'Hotel agora aparece no site.' : 'Hotel ocultado do site.'];
}

// Exclui o hotel (e seus quartos) só se não houver nenhuma reserva ligada a ele.
function excluirHotel(PDO $pdo, int $id): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM hoteis WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Hotel não encontrado.'];
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas r
               JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
              WHERE t.hotel_id = :id'
        );
        $stmt->execute(['id' => $id]);
        $reservas = (int) $stmt->fetchColumn();
        if ($reservas > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Este hotel tem ' . plural($reservas, 'reserva', 'reservas')
                . ' e não pode ser excluído. Oculte-o do site em vez disso.'];
        }

        // Os tipos de quarto saem junto (ON DELETE CASCADE)
        $stmt = $pdo->prepare('DELETE FROM hoteis WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pdo->commit();

    } catch (Throwable $erro) {
        // Também cai aqui se alguém reservar no mesmo instante: o banco (FK) barra a exclusão
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => 'Não foi possível excluir. O hotel pode ter reservas.'];
    }
    return ['ok' => true, 'msg' => 'Hotel excluído.'];
}