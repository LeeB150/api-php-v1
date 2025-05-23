<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSysUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sys_users');
        $table->addColumn('first_name', 'string', ['limit' => 255])
              ->addColumn('last_name', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addIndex(['email'], ['unique' => true])
              ->addColumn('username', 'string', ['limit' => 255])
              ->addIndex(['username'], ['unique' => true])
              ->addColumn('password', 'string', ['limit' => 255])
              ->addColumn('remember_token', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('profile_image', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('phone', 'string', ['limit' => 20, 'null' => true, 'default' => null])
              ->addColumn('timezone', 'string', ['limit' => 50, 'null' => true, 'default' => null])
              ->addColumn('locale', 'string', ['limit' => 10, 'null' => true, 'default' => null])
              ->addColumn('email_verified_at', 'timestamp', ['null' => true, 'default' => null])
              ->addColumn('id_role', 'integer', ['null' => true, 'default' => null, 'signed' => false])
              ->addColumn('id_status', 'integer', ['null' => true, 'default' => null, 'signed' => false])
              ->addColumn('created_by', 'integer', ['null' => true, 'default' => null])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_by', 'integer', ['null' => true, 'default' => null])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
              ->addForeignKey('id_role', 'sys_roles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('id_status', 'sys_status', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->create();
    }
}
