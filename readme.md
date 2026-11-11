# GOA Reservas

Sistema de reservas de hotéis em **PHP 8 + MySQL**: site para o hóspede reservar e painel
administrativo para a equipe controlar reservas, hotéis e quartos.

Projeto desenvolvido como segunda avaliação técnica (nova versão do site GOA Hotéis, com
gerenciador e controle de reservas).

## O que o sistema faz

**Site do hóspede**

- Home com busca, lista de hotéis e página de cada hotel
- Escolha de datas e hóspedes, com disponibilidade e preço calculados na hora
- Formulário de dados do hóspede e página de confirmação com código da reserva (ex.: `GOA7K2QX`)

**Painel administrativo** (`/admin`)

- Login com sessão, logout e proteção CSRF
- Painel com números por status e últimas reservas
- Lista de reservas com busca, filtros (status, hotel, datas) e paginação
- Detalhe da reserva e troca de status (pendente, confirmada, cancelada, concluída)
- Cadastro de hotéis: criar, editar, mostrar/ocultar no site e excluir
- Cadastro de tipos de quarto de cada hotel (preço, capacidade, quantidade)

## Como rodar (Windows + Laragon)

1. Clone o projeto em `C:\laragon\www\goa-reservas` e inicie o Laragon (Apache + MySQL).
2. Abra o HeidiSQL, conecte (usuário `root`, senha vazia) e execute, nesta ordem:
   - `database/schema.sql` (cria o banco `goa_reservas` e as tabelas)
   - `database/dados_exemplo.sql` (3 hotéis, 6 tipos de quarto e 1 administrador)
3. Acesse `http://localhost/goa-reservas/public/`.
4. Painel: `http://localhost/goa-reservas/public/admin/`

Acesso de exemplo (**somente para desenvolvimento**): `admin@goa.test` / `admin123`.

### Trocar a senha do administrador

```
php -r "echo password_hash('NovaSenhaForte', PASSWORD_DEFAULT);"
```

Copie o resultado e rode no banco:

```
UPDATE usuarios SET senha_hash = 'COLE_O_HASH_AQUI' WHERE email = 'admin@goa.test';
```

## Estrutura

```
config/      configurações e conexão com o banco
database/    schema.sql e dados_exemplo.sql
includes/    funções e partes de página reutilizadas
public/      tudo que o navegador acessa
  admin/     painel administrativo
  css/ js/   estilo e comportamento
```

## Regras de negócio

- **Disponibilidade:** um quarto está ocupado quando existe reserva com
  `checkin_existente < checkout_novo` e `checkout_existente > checkin_novo`. Os sinais são
  estritos: quem sai no dia 13 libera o quarto para quem entra no dia 13.
- Só reservas **pendentes** e **confirmadas** ocupam quarto. Cada tipo tem uma quantidade de unidades.
- A reserva só é aceita se a data de entrada não for passada, a estadia tiver no máximo 30 noites
  e os hóspedes couberem no quarto.
- **Sem overbooking:** a gravação da reserva roda numa transação com `SELECT ... FOR UPDATE`
  no tipo de quarto; duas pessoas reservando a última unidade ao mesmo tempo: só uma passa.
- O **preço e o status são sempre calculados no servidor**, nunca vêm do navegador.
- Status: `pendente` pode ir para `confirmada` ou `cancelada`; `confirmada` para `concluida` ou
  `cancelada`. Cancelada e concluída são finais.
- Reduzir a quantidade de quartos de um tipo é recusado se houver reservas ativas simultâneas
  acima do novo valor.
- Hotel ou quarto **com reservas não é excluído** (só oculto do site), para preservar o histórico.

## Segurança

- Consultas com **PDO e prepared statements** (proteção contra SQL injection)
- Toda saída escapada com `htmlspecialchars` (proteção contra XSS)
- Senhas guardadas com `password_hash` (nunca em texto puro) e conferidas com `password_verify`
- Sessão com cookie `HttpOnly` e `SameSite=Lax`; novo ID de sessão a cada login; expira após 30 min parado
- Token **CSRF** em todos os formulários do painel; ações que mudam dados só por POST
- Mensagem de login única ("e-mail ou senha incorretos"), sem revelar se o e-mail existe
- Cabeçalhos `X-Frame-Options`, `X-Content-Type-Options` e `Referrer-Policy`
- Pastas `config`, `includes` e `database` bloqueadas por `.htaccess`
- Credenciais do banco podem vir de variáveis de ambiente (`GOA_DB_*`)

## Para publicar

1. Em `config/config.php`, trocar `AMBIENTE` para `'producao'`.
2. Criar um usuário próprio no MySQL (não usar `root`) e definir `GOA_DB_HOST`, `GOA_DB_NAME`,
   `GOA_DB_USER` e `GOA_DB_PASS` no servidor.
3. Apontar a raiz do site para a pasta `public/`.
4. Trocar a senha do administrador de exemplo (veja acima) ou apagar esse usuário.
5. Usar HTTPS (o cookie de sessão passa a ser enviado só por HTTPS automaticamente).

## O que ficou de fora

- Fotos, logo e favicon (a coluna `imagem` já existe; falta receber os arquivos e fazer o upload)
- Limite de tentativas de login (bloqueio contra força bruta)
- Envio de e-mail de confirmação ao hóspede
- Pagamento online
- Cadastro de vários administradores e recuperação de senha
- Calendário visual de ocupação
- Páginas institucionais e formulário de contato do site original

## Tecnologias

PHP 8 · MySQL/MariaDB (PDO) · HTML5 · CSS3 (sem frameworks) · JavaScript puro
