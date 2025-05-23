<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSysRolesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sys_roles');
        $table->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('status', 'integer', ['default' => 1, 'comment' => '0: Inactivo, 1: Activo'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}