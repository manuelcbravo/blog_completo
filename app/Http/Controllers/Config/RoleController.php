<?php

namespace App\Http\Controllers\Config;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Config\UpsertRoleRequest;
use App\Http\Resources\Config\PermissionResource;
use App\Http\Resources\Config\RoleResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $roles = Role::query()
            ->with('permissions')
            ->when($busqueda !== '', fn (Builder $query) => $query->whereLike('name', "%{$busqueda}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('config/roles/index', [
            'roles' => RoleResource::collection($roles->getCollection())->resolve(),
            'paginacion' => [
                'total' => $roles->total(),
                'currentPage' => $roles->currentPage(),
                'lastPage' => $roles->lastPage(),
                'prevUrl' => $roles->previousPageUrl(),
                'nextUrl' => $roles->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'permissions' => PermissionResource::collection(
                Permission::query()->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    public function store(UpsertRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = isset($data['id']) ? Role::query()->findOrFail((int) $data['id']) : new Role(['guard_name' => 'web']);

        $role->name = $data['name'];
        $role->save();
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', isset($data['id']) ? 'Rol actualizado correctamente.' : 'Rol creado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Los roles base del seeder no se eliminan; un rol con usuarios
        // asignados tampoco (primero hay que reasignarlos).
        if (Rol::tryFrom($role->name) !== null) {
            return back()->with('error', 'Los roles base de la plataforma no se pueden eliminar.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'El rol tiene usuarios asignados; reasígnalos antes de eliminarlo.');
        }

        $role->delete();

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}
