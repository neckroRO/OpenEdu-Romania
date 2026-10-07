<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetAdminUserPasswordRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

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

    public function update(
        UpdateAdminUserRequest $request,
        User $user
    ): JsonResponse {
        $this->ensureCentralUser($user);

        $validated = $request->validated();
        $admin = $request->user();

        if (
            $admin->is($user)
            && array_key_exists('role', $validated)
            && $validated['role'] !== UserRole::Admin->value
        ) {
            return ApiResponse::error(
                code: 'ADMIN_SELF_ROLE_CHANGE_NOT_ALLOWED',
                message: 'Administratorul nu își poate elimina propriul rol de administrator.',
                status: 409,
            );
        }

        if (
            $admin->is($user)
            && array_key_exists('is_active', $validated)
            && $validated['is_active'] === false
        ) {
            return ApiResponse::error(
                code: 'ADMIN_SELF_DEACTIVATION_NOT_ALLOWED',
                message: 'Administratorul nu își poate dezactiva propriul cont.',
                status: 409,
            );
        }

        $deactivating = (
            array_key_exists('is_active', $validated)
            && $validated['is_active'] === false
            && $user->is_active
        );

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('role', $validated)) {
            $user->role = UserRole::from(
                $validated['role']
            );
        }

        if (array_key_exists('is_active', $validated)) {
            $user->is_active = $validated['is_active'];
        }

        $user->save();

        if ($deactivating) {
            $user->tokens()->delete();
        }

        return ApiResponse::success(
            (new AuthenticatedUserResource($user))
                ->resolve()
        );
    }

    public function resetPassword(
        ResetAdminUserPasswordRequest $request,
        User $user
    ): JsonResponse {
        $this->ensureCentralUser($user);

        $validated = $request->validated();

        $user->password = $validated['password'];
        $user->save();

        $user->tokens()->delete();

        return ApiResponse::success([
            'message' => 'Parola utilizatorului a fost resetată.',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->role->isAdmin() !== true) {
            throw new AccessDeniedHttpException();
        }
    }

    private function ensureCentralUser(User $user): void
    {
        if (! in_array(
            $user->role,
            [
                UserRole::Teacher,
                UserRole::Moderator,
                UserRole::Admin,
            ],
            true
        )) {
            throw new NotFoundHttpException();
        }
    }
}
