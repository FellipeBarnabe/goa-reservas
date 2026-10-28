# GOA Reservas

Sistema de reservas para uma rede de hotéis, com site público e painel administrativo.
Feito com PHP, MySQL e PDO.

## Status

- [x] Fundação: banco de dados, conexão, layout base
- [ ] Site público: hotéis e quartos
- [ ] Reservas com checagem de disponibilidade
- [ ] Painel administrativo (login, hotéis, quartos, reservas)
- [ ] Acabamento e documentação completa

## Como rodar localmente

1. Copie a pasta para a raiz do servidor local (Laragon: `C:\laragon\www\`).
2. Inicie o Apache e o MySQL.
3. Importe `database/schema.sql` e, se quiser dados de teste, `database/dados_exemplo.sql`.
4. Confira os dados de acesso em `config/database.php`.
5. Acesse `http://localhost/goa-reservas/`.

Painel de teste: `admin@goa.test` / `admin123` (apenas para ambiente local).

## Tecnologias

PHP 8+ · MySQL 8 · PDO · HTML5 · CSS3 · JavaScript
