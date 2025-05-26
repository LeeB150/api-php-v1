<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use App\Entity\User;

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
            ->addColumn('id_status', 'integer', ['null' => true, 'default' => 1, 'signed' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_by', 'integer', ['null' => true, 'default' => null])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addForeignKey('id_role', 'sys_roles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('id_status', 'sys_status', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        // Hashear la contraseña de 'admin1234' usando el algoritmo configurado en el factory
        $factory = new PasswordHasherFactory([
            User::class => ['algorithm' => 'auto'],
        ]);
        $userPasswordHasher = new UserPasswordHasher($factory);

        // Se crea una instancia vacía de User (la entidad) para pasarla al hasher
        $dummyUser = new User();
        $hashedPassword = $userPasswordHasher->hashPassword($dummyUser, 'admin1234');

        // Definir los datos del usuario admin por defecto
        $data = [
            'first_name' => 'admin',
            'last_name'  => 'admin',
            'email'      => 'admin@admin.com',
            'username'   => 'admin',
            'password'   => $hashedPassword,
            'id_role'    => 1,
            'id_status'  => 1
        ];

        // Insertar el usuario admin en la tabla
        $this->table('sys_users')->insert($data)->saveData();
    }
}
