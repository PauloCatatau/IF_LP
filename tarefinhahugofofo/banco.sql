CREATE DATABASE IF NOT EXISTS `tarefinhahugofofo`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `tarefinhahugofofo`;

CREATE TABLE IF NOT EXISTS `cad_user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `emailnumero` VARCHAR(255) NOT NULL,
    `senha` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cad_user_emailnumero` (`emailnumero`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tarefas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NOT NULL,
    `titulo` VARCHAR(255) NOT NULL,
    `descricao` TEXT NULL,
    `prioridade` ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'media',
    `status` ENUM('pendente', 'em andamento', 'concluida') NOT NULL DEFAULT 'pendente',
    `prazo` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tarefas_usuario_created` (`usuario_id`, `created_at`),
    KEY `idx_tarefas_usuario_status` (`usuario_id`, `status`),
    KEY `idx_tarefas_usuario_prioridade` (`usuario_id`, `prioridade`),
    KEY `idx_tarefas_prazo` (`prazo`),
    CONSTRAINT `fk_tarefas_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `cad_user` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `recuperacao_senha` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `token` CHAR(64) NOT NULL,
    `expiracao` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_recuperacao_token` (`token`),
    KEY `idx_recuperacao_email` (`email`),
    KEY `idx_recuperacao_expiracao` (`expiracao`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `historico_recuperacao` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `enviado` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_historico_recuperacao_email` (`email`),
    KEY `idx_historico_recuperacao_created` (`created_at`)
) ENGINE=InnoDB;
