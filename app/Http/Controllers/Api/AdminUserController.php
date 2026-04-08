<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        return UserResource::collection(
            User::query()->orderBy('id')->paginate(50)
        );
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'is_superuser' => ['required', 'boolean'],
        ]);

        if (! $data['is_superuser'] && $user->is_superuser) {
            $otherSuper = User::query()->where('is_superuser', true)->where('id', '!=', $user->id)->exists();
            if (! $otherSuper) {
                return response()->json([
                    'message' => __('admin.cannot_demote_last_superuser'),
                ], 422);
            }
        }

        $user->update(['is_superuser' => $data['is_superuser']]);

        return new UserResource($user->fresh());
    }
}
