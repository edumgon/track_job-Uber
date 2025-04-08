CREATE TABLE IF NOT EXISTS users (
	    id INT AUTO_INCREMENT PRIMARY KEY,
	    name VARCHAR(255) NOT NULL,
	    email VARCHAR(255) UNIQUE NOT NULL,
	    session_token VARCHAR(255) DEFAULT NULL,
	    session_last_active DATETIME DEFAULT NULL,
	    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

    DELIMITER //

    CREATE EVENT invalidate_sessions
    ON SCHEDULE EVERY 1 MINUTE
    DO
    BEGIN
        -- Atualiza as sessões inativas (mais de 5 minutos)
        UPDATE users
        SET session_token = NULL,
            session_last_active = NULL
        WHERE session_last_active IS NOT NULL
        AND TIMESTAMPDIFF(MINUTE, session_last_active, NOW()) > 5;
    END //

    DELIMITER ;

	INSERT INTO users (name, email) VALUES
	('John Doe', 'teste@teste.com'),
	('Jane Smith', 'teste2@teste.com');

-- Criação do banco de dados, se não existir
-- CREATE DATABASE IF NOT EXISTS my_database;
-- USE my_database;

-- Criação da tabela `applications`
CREATE TABLE IF NOT EXISTS applications (
    job_link_hash VARCHAR(32) PRIMARY KEY,          -- Link da vaga será a chave primária
    job_link VARCHAR(2083) NOT NULL,          -- Link da vaga será a chave primária
    company_name VARCHAR(255) NOT NULL,          -- Nome da empresa
    job_title VARCHAR(255) NOT NULL,             -- Nome da vaga
    application_date DATE NOT NULL,              -- Data da candidatura
    status ENUM('inicial', 'entrevista', 'proposta', 'negada', 'aprovado') DEFAULT 'inicial', -- Status
    return_date DATE,                            -- Data de retorno
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data de criação do registro
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Última atualização
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DELIMITER //

CREATE TRIGGER before_insert_users
BEFORE INSERT ON applications
FOR EACH ROW
BEGIN
    SET NEW.job_link_hash = MD5(NEW.job_link);
END //

DELIMITER ;

CREATE TABLE IF NOT EXISTS uber (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motorista VARCHAR(255) NOT NULL,
    distancia DECIMAL(5, 2) NOT NULL,
    tempo VARCHAR(10) NOT NULL,
    origem TEXT NOT NULL,
    destino TEXT NOT NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    passageiro VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    hr_origem TIME NOT NULL,
    hr_destino TIME NOT NULL,
    data_hr DATE NOT NULL,
    data_pgto DATE NULL,
    UNIQUE KEY unique_viagem (data_hr, hr_origem)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

