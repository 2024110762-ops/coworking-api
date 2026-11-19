<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function users()
    {
        return $this->successResponse(
            User::with(['roles', 'permissions'])->get(),
            'Listado de usuarios y roles.'
        );
    }

    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$validated['role']]);

        return $this->successResponse(
            $user->load('roles', 'permissions'),
            'Rol asignado correctamente.'
        );
    }

    public function rolesAndPermissions()
    {
        return $this->successResponse([
            'roles'       => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
        ], 'Roles y permisos del sistema.');
    }
}