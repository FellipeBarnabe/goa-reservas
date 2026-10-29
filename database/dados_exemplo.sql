-- Dados de exemplo (nomes, descrições e preços são fictícios, para teste)
USE goa_reservas;

INSERT INTO hoteis (nome, slug, bandeira, cidade, uf, descricao) VALUES
('GOA Essence Hai',      'goa-essence-hai',      'ESSENCE', 'João Pessoa',    'PB', 'Refúgio íntimo à beira-mar, pensado para o descanso.'),
('GOA Essence CityMix',  'goa-essence-citymix',  'ESSENCE', 'Campina Grande', 'PB', 'Conforto e praticidade para quem visita a cidade a negócios ou a lazer.'),
('LAHG Hotel Gramado by GOA', 'lahg-hotel-gramado', 'ESSENCE', 'Gramado',    'RS', 'O acolhimento da Serra Gaúcha na temporada natalina.');

INSERT INTO tipos_quarto (hotel_id, nome, descricao, capacidade, preco_diaria, quantidade) VALUES
(1, 'Standard',      'Quarto confortável com cama queen.',           2, 320.00, 10),
(1, 'Suíte Vista Mar','Suíte ampla com varanda e vista para o mar.', 3, 540.00,  4),
(2, 'Executivo',     'Quarto com mesa de trabalho e Wi-Fi rápido.',  2, 260.00, 12),
(2, 'Família',       'Dois ambientes, ideal para famílias.',         4, 420.00,  5),
(3, 'Superior',      'Quarto aconchegante com lareira.',             2, 480.00,  8),
(3, 'Suíte Família', 'Suíte com sala e duas camas.',                 4, 690.00,  3);

-- Administrador de teste: e-mail admin@goa.test | senha admin123 (trocar em produção)
INSERT INTO usuarios (nome, email, senha_hash) VALUES
('Administrador', 'admin@goa.test', '$2y$10$UUAdE7bSxKU63fVedBKo0e0bZVSbZ/.FI43etSIQMBcUXwppICpN6');
