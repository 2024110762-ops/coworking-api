<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\SpaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Permisos (en inglés)
        $permissions = [
            'spaces.view',
            'spaces.create',
            'spaces.edit',
            'spaces.delete',
            'reservations.view',
            'reservations.create',
            'reservations.approve',
            'reservations.complete',
            'users.manage',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Crear Roles y asignar permisos
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->givePermissionTo([
            'spaces.view',
            'reservations.view',
            'reservations.approve',
            'reservations.complete',
        ]);

        $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $clientRole->givePermissionTo([
            'spaces.view',
            'reservations.view',
            'reservations.create',
        ]);

        // 3. Crear Usuarios de Prueba por rol
        $admin = User::firstOrCreate(
            ['email' => 'admin@coworking.com'],
            ['name' => 'Administrador General', 'password' => Hash::make('password123')]
        );
        $admin->assignRole($adminRole);

        $staff = User::firstOrCreate(
            ['email' => 'staff@coworking.com'],
            ['name' => 'Recepción Staff', 'password' => Hash::make('password123')]
        );
        $staff->assignRole($staffRole);

        $client = User::firstOrCreate(
            ['email' => 'cliente@coworking.com'],
            ['name' => 'Juan Pérez (Cliente)', 'password' => Hash::make('password123')]
        );
        $client->assignRole($clientRole);

        // 4. Crear Tipos de Espacio y Espacios de ejemplo (datos en español)
        $sala = SpaceType::firstOrCreate(['name' => 'Sala de Reuniones'], ['description' => 'Salas equipadas con proyector y pizarra']);
        $escritorio = SpaceType::firstOrCreate(['name' => 'Escritorio Flexible'], ['description' => 'Puestos individuales en área compartida']);
        $cabina = SpaceType::firstOrCreate(['name' => 'Cabina Privada'], ['description' => 'Oficina cerrada para equipos pequeños']);

        Space::firstOrCreate(
            ['name' => 'Sala de Reuniones A'],
            ['space_type_id' => $sala->id, 'floor' => 'Piso 1', 'capacity' => 10, 'price_per_hour' => 45.00, 'is_active' => true]
        );

        Space::firstOrCreate(
            ['name' => 'Escritorio Flexible 01'],
            ['space_type_id' => $escritorio->id, 'floor' => 'Piso 2', 'capacity' => 1, 'price_per_hour' => 12.50, 'is_active' => true]
        );

        Space::firstOrCreate(
            ['name' => 'Cabina Privada Ejecutiva'],
            ['space_type_id' => $cabina->id, 'floor' => 'Piso 3', 'capacity' => 4, 'price_per_hour' => 30.00, 'is_active' => true]
        );
    }
}