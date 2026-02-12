<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $query = Plan::query()->where('is_active', true);

        if ($request->filled('search')) {
            $query->where('name', 'ilike', "%{$request->query('search')}%");
        }

        $plans = $query->orderBy('sort_order')->orderBy('price')->get();

        return $this->success(PlanResource::collection($plans));
    }

    public function show(Plan $plan): JsonResponse
    {
        return $this->resource(new PlanResource($plan));
    }
}
