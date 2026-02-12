<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponder;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant', 'roles');

        return $this->resource(new ProfileResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->resource(new ProfileResource($user->fresh('tenant', 'roles')));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return $this->success(['message' => 'Password updated successfully.']);
    }
}
