<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role->isAdmin() !== true) {
            throw new AccessDeniedHttpException();
        }

        $users = User::query()
            ->whereIn('role', [
                UserRole::Teacher->value,
                UserRole::Moderator->value,
                UserRole::Admin->value,
            ])
            ->orderBy('name')
            ->get()
            ->map(
                fn (User $user) =>
                    (new AuthenticatedUserResource($user))
                        ->resolve()
            )
            ->values()
            ->all();

        return ApiResponse::success($users);
    }

    public function store(
        StoreAdminUserRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];
        $user->role = UserRole::from(
            $validated['role']
        );
        $user->save();

        return ApiResponse::created(
            (new AuthenticatedUserResource($user))
                ->resolve()
        );
    }
}
