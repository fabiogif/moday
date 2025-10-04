<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreUpdateProfile;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected Profile $repository)
    {
    }
    public function index()
    {
        $profiles = $this->repository->latest()->paginate(10);
        return view('admin.pages.profile.index', ['profiles' => $profiles]);
    }

    public function store(StoreUpdateProfile $request)
    {
        $this->repository->create($request->all());
        return redirect()->route('profiles.index');
    }

    public function create()
    {
        return view('admin.pages.profile.create');

    }

    public function update(StoreUpdateProfile $request, $id)
    {
        $profile = $this->repository->where('id', $id)->first();

        if(!$profile){
            return  redirect()->back();
        }

        $profile->update($request->all());

        return redirect()->route('profiles.index');
    }

    public function edit($id)
    {
        $profile = $this->repository->findOrFail($id);

        return view('admin.pages.profile.edit', ['profile' => $profile]);

    }

    public function show($id)
    {
    $profile = $this->repository->findOrFail($id);

    return view('admin.pages.profile.show', ['profile' => $profile]);
    }

    public function search(Request $request)
    {
        $filters = $request->except('_token');

        $profile = $this->repository->search($request->filter);

        return view('admin.pages.profile.index', ['profiles' => $profile, 'filters' => $filters] );
    }

    public function destroy($id)
    {
        $profile = $this->repository->findOrFail($id);
        if($profile){
            $profile->delete();

        }
        return redirect()->route('profiles.index');
    }
}
