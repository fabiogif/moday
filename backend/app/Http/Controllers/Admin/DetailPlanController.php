<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreUpdateDetailPlanRequest;
use App\Models\DetailPlan;
use App\Models\Plan;

class DetailPlanController extends Controller
{
    public function __construct(protected  DetailPlan $repository, protected  Plan $plan)
    {
    }

    public function index($idPlan)
    {
        $plan = $this->plan->where('id', $idPlan)->first();

        if(!$plan){
            return redirect()->back();
        }
        $details = $plan->details()->paginate();

        return view('admin.pages.plans.details.index', compact('details', 'plan'));
    }

    public function create($idPlan)
    {
        $plan = $this->plan->where('id', $idPlan)->first();

        if(!$plan){
            return redirect()->back();
        }

        return view('admin.pages.plans.details.create', compact('plan'));

    }

    public function store(StoreUpdateDetailPlanRequest $request, $idPlan)
    {
        $plan = $this->plan->where('id', $idPlan)->first();

        if(!$plan){
            return redirect()->back();
        }

        $plan->details()->create($request->all());
        return redirect()->route('details.plans.index', $plan->id);
    }

    public function edit($idPlan, $idDetalhes)
    {
        $detail = $this->repository->where('id', $idDetalhes)->first();
        $plan = $this->plan->where('id', $idPlan)->first();

        if(!$detail){
            return redirect()->back();
        }
        return view('admin.pages.plans.details.edit', ['detail' => $detail, 'plan' => $plan]);
    }

    public function update(StoreUpdateDetailPlanRequest $request, $idPlan, $idDetalhes)
    {

        $detail = $this->repository->where('id', $idDetalhes)->first();
        $plan = $this->plan->where('id', $idPlan)->first();

        if(!$detail || !$plan){
            return redirect()->back();
        }

        $detail->update($request->all());

        return redirect()->route('details.plans.index', $plan->id );
    }
}
