<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Assign a role to a user
     */
    public function assign(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = Role::where('name', $request->role)->first();

        if ($user->roles()->where('role_id', $role->id)->exists()) {
            return response()->json([
                'message' => 'User already has this role'
            ], 409);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'roles' => $user->roles()->pluck('name')
        ]);
    }

    /**
     * Revoke a role from a user
     */
    public function revoke(Request $request, User $user, Role $role)
    {
        // Prevent admin from removing their own admin role
        if (
            $request->user()->id === $user->id &&
            $role->name === 'admin'
        ) {
            return response()->json([
                'message' => 'You cannot remove your own admin role.'
            ], 403);
        }

        // Check if user actually has the role
        if (!$user->roles()->where('role_id', $role->id)->exists()) {
            return response()->json([
                'message' => 'User does not have this role'
            ], 404);
        }

        $user->roles()->detach($role->id);

        return response()->json([
            'message' => 'Role revoked successfully',
            'roles' => $user->roles()->pluck('name')
        ]);
    }

    /**
     * List all roles of a user
     */
    public function userRoles(User $user)
    {
        return response()->json([
            'roles' => $user->roles()->pluck('name')
        ]);
    }
}
