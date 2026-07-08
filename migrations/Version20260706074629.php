<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260706074629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : comptes (héritage STI), transactions, catégories, budgets, objectifs, transactions récurrentes, conseillers, notifications, audit et taux de change.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounts (name VARCHAR(100) NOT NULL, currency VARCHAR(3) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, owner_id UUID NOT NULL, type VARCHAR(30) NOT NULL, overdraft_limit NUMERIC(10, 2) DEFAULT NULL, interest_rate NUMERIC(5, 2) DEFAULT NULL, credit_limit NUMERIC(10, 2) DEFAULT NULL, statement_day SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CAC89EAC7E3C61F9 ON accounts (owner_id)');
        $this->addSql('CREATE TABLE advisor_assignments (assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, advisor_id UUID NOT NULL, client_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_AF9D50F666D3AD77 ON advisor_assignments (advisor_id)');
        $this->addSql('CREATE INDEX IDX_AF9D50F619EB6921 ON advisor_assignments (client_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_advisor_client ON advisor_assignments (advisor_id, client_id)');
        $this->addSql('CREATE TABLE attachments (filename VARCHAR(255) NOT NULL, path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, transaction_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_47C4FAD62FC0CB0F ON attachments (transaction_id)');
        $this->addSql('CREATE TABLE audit_logs (action VARCHAR(20) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id VARCHAR(36) NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, user_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D62F2858A76ED395 ON audit_logs (user_id)');
        $this->addSql('CREATE TABLE budgets (period_start DATE NOT NULL, limit_amount NUMERIC(10, 2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, owner_id UUID NOT NULL, category_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DCAA95487E3C61F9 ON budgets (owner_id)');
        $this->addSql('CREATE INDEX IDX_DCAA954812469DE2 ON budgets (category_id)');
        $this->addSql('CREATE TABLE categories (name VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, icon VARCHAR(10) DEFAULT NULL, id UUID NOT NULL, owner_id UUID DEFAULT NULL, parent_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_3AF346687E3C61F9 ON categories (owner_id)');
        $this->addSql('CREATE INDEX IDX_3AF34668727ACA70 ON categories (parent_id)');
        $this->addSql('CREATE TABLE exchange_rates (base_currency VARCHAR(3) NOT NULL, target_currency VARCHAR(3) NOT NULL, rate NUMERIC(18, 8) NOT NULL, fetched_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_currency_pair ON exchange_rates (base_currency, target_currency)');
        $this->addSql('CREATE TABLE notifications (type VARCHAR(30) NOT NULL, message VARCHAR(500) NOT NULL, is_read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
        $this->addSql('CREATE TABLE recurring_transactions (description VARCHAR(255) DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, frequency VARCHAR(20) NOT NULL, next_run_date DATE NOT NULL, active BOOLEAN NOT NULL, id UUID NOT NULL, account_id UUID NOT NULL, category_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2468994C9B6B5FBA ON recurring_transactions (account_id)');
        $this->addSql('CREATE INDEX IDX_2468994C12469DE2 ON recurring_transactions (category_id)');
        $this->addSql('CREATE TABLE savings_goals (name VARCHAR(100) NOT NULL, target_amount NUMERIC(10, 2) NOT NULL, deadline DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, owner_id UUID NOT NULL, account_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EF09AB977E3C61F9 ON savings_goals (owner_id)');
        $this->addSql('CREATE INDEX IDX_EF09AB979B6B5FBA ON savings_goals (account_id)');
        $this->addSql('CREATE TABLE tags (name VARCHAR(50) NOT NULL, id UUID NOT NULL, owner_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6FBC94267E3C61F9 ON tags (owner_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_tag_owner_name ON tags (owner_id, name)');
        $this->addSql('CREATE TABLE transactions (type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, description VARCHAR(255) DEFAULT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, account_id UUID NOT NULL, category_id UUID DEFAULT NULL, transfer_to_account_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EAA81A4C9B6B5FBA ON transactions (account_id)');
        $this->addSql('CREATE INDEX IDX_EAA81A4C12469DE2 ON transactions (category_id)');
        $this->addSql('CREATE INDEX IDX_EAA81A4CBE7BF342 ON transactions (transfer_to_account_id)');
        $this->addSql('CREATE TABLE transaction_tag (transaction_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY (transaction_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_F8CD024A2FC0CB0F ON transaction_tag (transaction_id)');
        $this->addSql('CREATE INDEX IDX_F8CD024ABAD26311 ON transaction_tag (tag_id)');
        $this->addSql('CREATE TABLE users (email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, preferred_currency VARCHAR(3) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EAC7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE advisor_assignments ADD CONSTRAINT FK_AF9D50F666D3AD77 FOREIGN KEY (advisor_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE advisor_assignments ADD CONSTRAINT FK_AF9D50F619EB6921 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE attachments ADD CONSTRAINT FK_47C4FAD62FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE budgets ADD CONSTRAINT FK_DCAA95487E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE budgets ADD CONSTRAINT FK_DCAA954812469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF346687E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE recurring_transactions ADD CONSTRAINT FK_2468994C9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE recurring_transactions ADD CONSTRAINT FK_2468994C12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE savings_goals ADD CONSTRAINT FK_EF09AB977E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE savings_goals ADD CONSTRAINT FK_EF09AB979B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC94267E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CBE7BF342 FOREIGN KEY (transfer_to_account_id) REFERENCES accounts (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transaction_tag ADD CONSTRAINT FK_F8CD024A2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transaction_tag ADD CONSTRAINT FK_F8CD024ABAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP CONSTRAINT FK_CAC89EAC7E3C61F9');
        $this->addSql('ALTER TABLE advisor_assignments DROP CONSTRAINT FK_AF9D50F666D3AD77');
        $this->addSql('ALTER TABLE advisor_assignments DROP CONSTRAINT FK_AF9D50F619EB6921');
        $this->addSql('ALTER TABLE attachments DROP CONSTRAINT FK_47C4FAD62FC0CB0F');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858A76ED395');
        $this->addSql('ALTER TABLE budgets DROP CONSTRAINT FK_DCAA95487E3C61F9');
        $this->addSql('ALTER TABLE budgets DROP CONSTRAINT FK_DCAA954812469DE2');
        $this->addSql('ALTER TABLE categories DROP CONSTRAINT FK_3AF346687E3C61F9');
        $this->addSql('ALTER TABLE categories DROP CONSTRAINT FK_3AF34668727ACA70');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE recurring_transactions DROP CONSTRAINT FK_2468994C9B6B5FBA');
        $this->addSql('ALTER TABLE recurring_transactions DROP CONSTRAINT FK_2468994C12469DE2');
        $this->addSql('ALTER TABLE savings_goals DROP CONSTRAINT FK_EF09AB977E3C61F9');
        $this->addSql('ALTER TABLE savings_goals DROP CONSTRAINT FK_EF09AB979B6B5FBA');
        $this->addSql('ALTER TABLE tags DROP CONSTRAINT FK_6FBC94267E3C61F9');
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4C9B6B5FBA');
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4C12469DE2');
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4CBE7BF342');
        $this->addSql('ALTER TABLE transaction_tag DROP CONSTRAINT FK_F8CD024A2FC0CB0F');
        $this->addSql('ALTER TABLE transaction_tag DROP CONSTRAINT FK_F8CD024ABAD26311');
        $this->addSql('DROP TABLE accounts');
        $this->addSql('DROP TABLE advisor_assignments');
        $this->addSql('DROP TABLE attachments');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE budgets');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE exchange_rates');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE recurring_transactions');
        $this->addSql('DROP TABLE savings_goals');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE transaction_tag');
        $this->addSql('DROP TABLE users');
    }
}
