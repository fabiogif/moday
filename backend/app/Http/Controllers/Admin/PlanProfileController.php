<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\attachPlanProfileRequest;
use App\Models\{Plan, Profile};
use Illuminate\Http\Request;

class PlanProfileController extends Controller
{

    public  function __construct(protected Plan $plan, protected Profile $profile)
    {
    }

    public function profiles($idPlan)
    {

        $plan = $this->plan->find($idPlan);
        if(!$plan)
        {
            return redirect()->back();
        }
        $profiles  = $plan->profiles()->paginate();

        return view('admin.pages.plans.profiles.index', compact('plan', 'profiles'));
    }


    public function plans($idProfile)
    {

        $profiles = $this->profile->find($idProfile);
        if(!$profiles)
        {
            return redirect()->back();
        }
        $plans = $profiles->plans()->paginate();

        return view('admin.pages.profiles.plans.index', compact('profiles', 'plans'));
    }

    public function profilesAvailable(Request $request, $idPlan)
    {

        $plan = $this->plan->find($idPlan);

        if(!$plan)
        {
            return redirect()->back();
        }

        $filters = $request->except('_token');

        $profiles = $plan->profilesAvailable($request->filter);
        return view('admin.pages.plans.profiles.available', compact('plan', 'profiles', 'filters'));
    }


    public function attachProfilePlan(attachPlanProfileRequest $request, $idPlan)
    {
        $plan = $this->plan->find($idPlan);


        if(!$plan)
        {
            return redirect()->back();
        }
        $plan->profiles()->attach($request->profiles);

        return redirect()->route('plans.profiles', $idPlan);

    }

    public function detachProfilePlan($idPlan, $idProfile)
    {
        $plan = $this->plan->find($idPlan);
        $profile = $this->profile->find($idProfile);

        if(!$plan || !$profile)
        {
            return redirect()->back();
        }

        $plan->profiles()->detach($profile); //Passando todo objeto

        return redirect()->route('plans.profiles', $idPlan);

    }





}
