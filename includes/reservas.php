<?php
// Regras de negócio das reservas: datas, hóspedes e disponibilidade.

const MAX_NOITES = 30;

// "1 noite", "3 noites"
function plural(int $n, string $singular, string $plural): string
{
    return $n . ' ' . ($n === 1 ? $singular : $plural);
}

// Converte "2026-11-10" em data. Devolve null se o texto não for uma data real.
function parseData($valor): ?DateTimeImmutable
{
    if (!is_string($valor)) {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $valor, new DateTimeZone('UTC'));
    return ($d !== false && $d->format('Y-m-d') === $valor) ? $d : null;
}

// Quantas noites há entre o check-in e o check-out
function contarNoites(string $checkin, string $checkout): int
{
    $a = parseData($checkin);
    $b = parseData($checkout);
    if (!$a || !$b) {
        return 0;
    }
    return (int) $a->diff($b)->format('%r%a');
}


// Lê um número inteiro da entrada. Ausente = padrão. Inválido = -1 (a validação recusa).
function inteiroDe(array $entrada, string $campo, int $padrao): int
{
    if (!isset($entrada[$campo])) {
        return $padrao;
    }
    $v = $entrada[$campo];
    return is_string($v) && ctype_digit($v) ? (int) $v : -1;
}

// Lê datas e número de hóspedes (de $_GET ou $_POST)
function lerPeriodo(array $entrada): array
{
    $texto = static fn(string $campo): string =>
        isset($entrada[$campo]) && is_string($entrada[$campo]) ? trim($entrada[$campo]) : '';

    return [
        'checkin'  => $texto('checkin'),
        'checkout' => $texto('checkout'),
        'adultos'  => inteiroDe($entrada, 'adultos', 2),
        'criancas' => inteiroDe($entrada, 'criancas', 0),
    ];
}

// Valida o período e devolve a lista de erros (vazia = tudo certo)
function validarPeriodo(array $p): array
{
    $erros    = [];
    $checkin  = parseData($p['checkin']);
    $checkout = parseData($p['checkout']);

    if (!$checkin) {

        $erros[] = 'Informe uma data de check-in válida.';
    } elseif ($p['checkin'] < date('Y-m-d')) {
        $erros[] = 'O check-in não pode ser no passado.';
    }

    if (!$checkout) {
        $erros[] = 'Informe uma data de check-out válida.';
    }

    if ($checkin && $checkout) {
        $noites = contarNoites($p['checkin'], $p['checkout']);
        if ($noites < 1) {
            $erros[] = 'O check-out deve ser depois do check-in.';
        } elseif ($noites > MAX_NOITES) {
            $erros[] = 'A estadia pode ter no máximo ' . MAX_NOITES . ' noites.';
        }
    }

    if ($p['adultos'] < 1 || $p['adultos'] > 6) {
        $erros[] = 'Informe de 1 a 6 adultos.';
    }
    if ($p['criancas'] < 0 || $p['criancas'] > 4) {
        $erros[] = 'Informe de 0 a 4 crianças.';
    }

    return $erros;
}

// Tipos de quarto do hotel, já com a disponibilidade para o período.
//
// REGRA CENTRAL: duas estadias se sobrepõem quando
//     checkin_existente < checkout_novo  E  checkout_existente > checkin_novo

// Usamos "<" e ">" (e não "<=" e ">=") porque o hóspede que sai no dia 13
// libera o quarto para quem entra no dia 13.
// Só reservas "pendente" e "confirmada" ocupam o quarto.
function listarTiposComDisponibilidade(PDO $pdo, int $hotelId, string $checkin, string $checkout, int $hospedes): array
{
    $stmt = $pdo->prepare(
        "SELECT t.id, t.nome, t.descricao, t.capacidade, t.preco_diaria, t.quantidade,
                (SELECT COUNT(*) FROM reservas r
                  WHERE r.tipo_quarto_id = t.id
                    AND r.status IN ('pendente', 'confirmada')
                    AND r.checkin  < :checkout
                    AND r.checkout > :checkin) AS ocupados
           FROM tipos_quarto t
          WHERE t.hotel_id = :hotel AND t.ativo = 1
          ORDER BY t.preco_diaria"
    );
    $stmt->execute(['checkout' => $checkout, 'checkin' => $checkin, 'hotel' => $hotelId]);

    $noites = contarNoites($checkin, $checkout);
    $tipos  = [];

    foreach ($stmt->fetchAll() as $t) {
        $t['disponiveis'] = max(0, (int) $t['quantidade'] - (int) $t['ocupados']);
        $t['cabe']        = (int) $t['capacidade'] >= $hospedes;
        $t['total']       = $noites * (float) $t['preco_diaria'];
        $tipos[] = $t;
    }

    return $tipos;
}

// Procura um tipo de quarto pelo id dentro da lista

function acharTipo(array $tipos, ?int $id): ?array
{
    if (!$id) {
        return null;
    }
    foreach ($tipos as $t) {
        if ((int) $t['id'] === $id) {
            return $t;
        }
    }
    return null;
}

// O quarto pode ser reservado? (comporta os hóspedes e ainda tem unidade livre)
function tipoReservavel(?array $tipo): bool
{
    return $tipo !== null && $tipo['cabe'] && $tipo['disponiveis'] > 0;
}

// Reúne tudo o que as páginas de reserva precisam, a partir de $_GET ou $_POST:
// hotel, período, erros, noites e quartos com disponibilidade.
function carregarContextoReserva(PDO $pdo, array $entrada): array
{
    $ctx = [
        'hotel'     => null,
        'periodo'   => lerPeriodo($entrada),
        'buscou'    => isset($entrada['checkin']) || isset($entrada['checkout']),
        'erros'     => [],
        'periodoOk' => false,
        'noites'    => 0,
        'tipos'     => [],
        'tipoId'    => null,

        'tipo'      => null,
    ];

    $hotelId = filter_var($entrada['hotel'] ?? null, FILTER_VALIDATE_INT);
    if ($hotelId) {
        $stmt = $pdo->prepare(
            'SELECT id, nome, bandeira, cidade, uf FROM hoteis WHERE id = :id AND ativo = 1'
        );
        $stmt->execute(['id' => $hotelId]);
        $ctx['hotel'] = $stmt->fetch() ?: null;
    }
    if (!$ctx['hotel']) {
        return $ctx;
    }

    $ctx['tipoId'] = filter_var($entrada['tipo'] ?? null, FILTER_VALIDATE_INT) ?: null;

    if ($ctx['buscou']) {
        $ctx['erros'] = validarPeriodo($ctx['periodo']);
    }
    $ctx['periodoOk'] = $ctx['buscou'] && empty($ctx['erros']);

    if ($ctx['periodoOk']) {
        $p = $ctx['periodo'];
        $ctx['noites'] = contarNoites($p['checkin'], $p['checkout']);
        $ctx['tipos']  = listarTiposComDisponibilidade(
            $pdo, (int) $ctx['hotel']['id'], $p['checkin'], $p['checkout'], $p['adultos'] + $p['criancas']
        );
        $ctx['tipo'] = acharTipo($ctx['tipos'], $ctx['tipoId']);
    }

    return $ctx;

}

// Monta o endereço de uma página do fluxo de reserva, mantendo hotel, datas e hóspedes
function urlReserva(string $pagina, array $ctx, array $extra = []): string
{
    $p = $ctx['periodo'];
    $params = [
        'hotel'    => (int) $ctx['hotel']['id'],
        'checkin'  => $p['checkin'],
        'checkout' => $p['checkout'],
        'adultos'  => $p['adultos'],
        'criancas' => $p['criancas'],
    ] + $extra;

    return url($pagina . '?' . http_build_query($params));
}