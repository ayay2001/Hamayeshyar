USE hamayesh_yar;

INSERT INTO users (name,email,phone,password_hash,role,status) VALUES
('نویسنده نمونه','author@test.com',NULL,'$2y$12$3047YHUFaH2WTaeTr6gpGuS.sTpnXIjZCRqcq6AsDphUxgHMvkdoK','author','active'),
('داور نمونه','reviewer@test.com',NULL,'$2y$12$UPZcUCh0pDhChAjbPXIrR.G/hTGTwwtmrbhk6W7hfuEz2gH8sINQi','reviewer','active'),
('دبیرخانه','secret@test.com',NULL,'$2y$12$SBNli8Fe5pnN4Tqepaw8ou2ZBGQIZOI25lU.4vo9IgLTP31G.yHhK','secretariat','active')
ON DUPLICATE KEY UPDATE email = VALUES(email);
