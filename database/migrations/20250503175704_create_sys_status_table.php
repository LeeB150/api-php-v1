<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSysStatusTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sys_status');
        $table->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // Insertar los estados por defecto
        $data = [
            ['name' => 'inactive'],
            ['name' => 'active'],
            ['name' => 'deleted']
        ];

        $table->insert($data)->saveData();
    }
}
