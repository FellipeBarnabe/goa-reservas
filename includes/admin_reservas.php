<?php
// Funções do painel: listar e filtrar reservas, mudar o status e avisar o admin.
// Precisa de reservas.php (STATUS_RESERVA, parseData, inteiroDe) e auth.php já carregados.

const POR_PAGINA = 10;

// Para onde cada status pode ir. Cancelada e concluída são finais:
// reativar uma reserva cancelada exigiria conferir a disponibilidade de novo.
const TRANSICOES_STATUS = [
    'pendente'   => ['confirmada', 'cancelada'],
    'confirmada' => ['concluida', 'cancelada'],
    'cancelada'  => [],
    'concluida'  => [],
];

// Lê e limpa os filtros da URL. Valor estranho vira "sem filtro".
function lerFiltros(array $get): array
{
    $status = is_string($get['status'] ?? null) && isset(STATUS_RESERVA[$get['status']]) ? $get['status'] : '';

    $hotel = inteiroDe($get, 'hotel', 0);
    $hotel = $hotel > 0 ? $hotel : 0;

    $de  = parseData($get['de'] ?? null)  ? $get['de']  : '';
    $ate = parseData($get['ate'] ?? null) ? $get['ate'] : '';

    $busca = is_string($get['busca'] ?? null) ? trim(mb_substr($get['busca'], 0, 60)) : '';

    $pagina = inteiroDe($get, 'pagina', 1);
    $pagina = $pagina > 0 ? $pagina : 1;

    return compact('status', 'hotel', 'de', 'ate', 'busca', 'pagina');

}

// Busca as reservas do filtro, uma página por vez.
// Devolve ['linhas' => [...], 'total' => N, 'paginas' => N, 'pagina' => N]
function listarReservas(PDO $pdo, array $f): array
{
    $onde   = [];
    $params = [];

    if ($f['status'] !== '') {
        $onde[] = 'r.status = :status';
        $params['status'] = $f['status'];
    }
    if ($f['hotel'] > 0) {
        $onde[] = 'h.id = :hotel';
        $params['hotel'] = $f['hotel'];
    }
    if ($f['de'] !== '') {
        $onde[] = 'r.checkin >= :de';
        $params['de'] = $f['de'];
    }
    if ($f['ate'] !== '') {
        $onde[] = 'r.checkin <= :ate';
        $params['ate'] = $f['ate'];
    }
    if ($f['busca'] !== '') {
        // % e _ digitados pelo admin valem como texto comum, não como curingas
        $like = '%' . addcslashes($f['busca'], '\\%_') . '%';
        $onde[] = '(r.codigo LIKE :b1 OR r.hospede_nome LIKE :b2 OR r.hospede_email LIKE :b3)';
        $params['b1'] = $params['b2'] = $params['b3'] = $like;
    }


    $filtro = $onde ? 'WHERE ' . implode(' AND ', $onde) : '';
    $from   = 'FROM reservas r
               JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
               JOIN hoteis h       ON h.id = t.hotel_id ' . $filtro;

    $stmt = $pdo->prepare('SELECT COUNT(*) ' . $from);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $paginas = max(1, (int) ceil($total / POR_PAGINA));
    $pagina  = min($f['pagina'], $paginas);
    $inicio  = ($pagina - 1) * POR_PAGINA;

    // Os números já são inteiros validados, por isso podem entrar direto no SQL
    $stmt = $pdo->prepare(
        'SELECT r.id, r.codigo, r.hospede_nome, r.checkin, r.checkout, r.valor_total, r.status,
                t.nome AS quarto, h.nome AS hotel '
        . $from . ' ORDER BY r.id DESC LIMIT ' . POR_PAGINA . ' OFFSET ' . $inicio
    );
    $stmt->execute($params);

    return ['linhas' => $stmt->fetchAll(), 'total' => $total, 'paginas' => $paginas, 'pagina' => $pagina];
}

// Endereço da lista mantendo os filtros ativos (usado na paginação)
function urlListaReservas(array $f, array $extra = []): string
{
    $q = [
        'busca'  => $f['busca'],
        'status' => $f['status'],
        'hotel'  => $f['hotel'] > 0 ? $f['hotel'] : '',
        'de'     => $f['de'],

        'ate'    => $f['ate'],
    ];
    $q = array_filter(array_merge($q, $extra), fn($v) => $v !== '' && $v !== null);
    if (isset($q['pagina']) && (int) $q['pagina'] <= 1) {
        unset($q['pagina']);
    }
    return url('admin/reservas.php') . ($q ? '?' . http_build_query($q) : '');
}

// Uma reserva completa (com hotel e quarto) ou null
function buscarReserva(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, t.nome AS quarto, h.nome AS hotel, h.cidade, h.uf
           FROM reservas r
           JOIN tipos_quarto t ON t.id = r.tipo_quarto_id
           JOIN hoteis h       ON h.id = t.hotel_id
          WHERE r.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// Muda o status se a mudança for permitida. Devolve ['ok' => bool, 'msg' => texto].
function alterarStatus(PDO $pdo, int $id, string $novo): array
{
    if (!isset(STATUS_RESERVA[$novo])) {
        return ['ok' => false, 'msg' => 'Status inválido.'];
    }

    $pdo->beginTransaction();
    try {

        // Trava a linha: dois admins clicando ao mesmo tempo não se atropelam
        $stmt = $pdo->prepare('SELECT status FROM reservas WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $atual = $stmt->fetchColumn();

        if ($atual === false) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Reserva não encontrada.'];
        }
        if (!in_array($novo, TRANSICOES_STATUS[$atual] ?? [], true)) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Não é possível mudar de ' . rotuloStatus($atual) . ' para ' . rotuloStatus($novo) . '.'];
        }

        $stmt = $pdo->prepare('UPDATE reservas SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $novo, 'id' => $id]);
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => 'Não foi possível salvar. Tente novamente.'];
    }

    return ['ok' => true, 'msg' => 'Status alterado para ' . rotuloStatus($novo) . '.'];
}

// ----- Aviso que sobrevive a um redirecionamento (mostra uma vez e some) -----

function definirAviso(string $tipo, string $texto): void
{
    iniciarSessao();

    $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegarAviso(): ?array
{
    iniciarSessao();
    $aviso = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $aviso;
}