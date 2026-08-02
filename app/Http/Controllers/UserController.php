<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerification;
use App\Models\Token;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['integer', 'min:1'],
            'perPage' => ['integer', 'min:1', 'max:100'],
            'search' => ['string', 'max:255'],
        ]);

        $page = $request->query('page', 1);
        $perPage = min($request->query('perPage', 10), 100);
        $search = $request->query('search', '');

        $users = User::search($search)
            ->paginate($perPage, ['*'], 'page', $page);

        return jsonResponse(data: [
                'users' => UserResource::collection($users),
                'pagination' => [
                    'total' => $users->total(),
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage(),
                    'next_page_url' => $users->nextPageUrl(),
                    'prev_page_url' => $users->previousPageUrl(),
                ]
            ],
        );
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return jsonResponse(
            data: ['user' => new UserResource($user)]
        );
    }

    /**
     * Display the specified user
     */
    public function store(StoreUserRequest $request)
    {
        return "";
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        return transactional(function () use ($request, $user){
            $user->fill($request->validated());

            if($user->isDirty()){
                $user->save();

                return jsonResponse(
                    message: 'User updated successfully',
                    data: ['user' => new UserResource($user->fresh())]
                );
            }

            return jsonResponse(
                message: 'No changes were made',
                data: ['user' => new UserResource($user)]
            );
        });
    }

    /**
     * Remove the specified user (Admin only)
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return jsonResponse(
                status: 400,
                message: 'You cannot delete your own account'
            );
        }

        return transactional(function () use ($user) {
            $userName = $user->name;
            $user->delete();

            return jsonResponse(
                message: "User '{$userName}' deleted successfully"
            );
        });
    }
}
