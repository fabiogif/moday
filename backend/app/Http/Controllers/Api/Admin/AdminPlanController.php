<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\PlanApiController;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminPlanController extends PlanApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return parent::index($request);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        return parent::store($request);
    }

    public function update(UpdatePlanRequest $request, $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    public function show($id): JsonResponse
    {
        return parent::show($id);
    }

    public function delete($id): JsonResponse
    {
        return parent::delete($id);
    }
}
