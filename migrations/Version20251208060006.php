<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208060006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id BINARY(16) NOT NULL, status VARCHAR(50) NOT NULL, winner VARCHAR(50) DEFAULT NULL, current_turn VARCHAR(1) DEFAULT NULL, board_state LONGTEXT NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, creator_user_id BINARY(16) NOT NULL, opponent_user_id BINARY(16) DEFAULT NULL, INDEX IDX_232B318C29FC6AE1 (creator_user_id), INDEX IDX_232B318CAA25099D (opponent_user_id), INDEX idx_game_status (status), INDEX idx_game_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE move (id BINARY(16) NOT NULL, position INT NOT NULL, symbol VARCHAR(1) NOT NULL, created_at DATETIME NOT NULL, move_number INT NOT NULL, game_id BINARY(16) NOT NULL, player_id BINARY(16) NOT NULL, INDEX IDX_EF3E3778E48FD905 (game_id), INDEX IDX_EF3E377899E6F5DF (player_id), INDEX idx_move_game_time (game_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_activity_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D6495E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C29FC6AE1 FOREIGN KEY (creator_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CAA25099D FOREIGN KEY (opponent_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E377899E6F5DF FOREIGN KEY (player_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C29FC6AE1');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CAA25099D');
        $this->addSql('ALTER TABLE move DROP FOREIGN KEY FK_EF3E3778E48FD905');
        $this->addSql('ALTER TABLE move DROP FOREIGN KEY FK_EF3E377899E6F5DF');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE move');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
