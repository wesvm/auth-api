<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerification;
use App\Models\Token;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:jwt')->except(['store']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('perPage', 10);
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
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        return transactional(function () use ($request){
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'user',
            ]);

            Token::where('user_id', $user->id)
                ->where('token_type', TokenType::EMAIL_VERIFICATION)
                ->delete();

            $token = Token::create([
                'token' => Str::uuid(),
                'token_type' => TokenType::EMAIL_VERIFICATION,
                'expires_at' => now()->addHours(2),
                'user_id' => $user->id,
            ]);
            Mail::to($user->email)->send(new EmailVerification($user->name, $token->token));
            return jsonResponse(message: 'Check your email for verification link.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return jsonResponse(data: ['user' => new UserResource($user)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return transactional(function () use ($request, $user){
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;

            if($user->isDirty()){
                $user->save();
                return jsonResponse(
                    message: 'User updated successfully',
                    data: ['user' => new UserResource($user)],
                );
            }

            return jsonResponse(message: 'No changes were made');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        return jsonResponse(message: 'Not implemented yet');
    }
}
