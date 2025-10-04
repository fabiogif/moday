<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\attachPermissioRequest;
use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Http\Request;

class PermissionProfileController extends Controller
{

    public  function __construct(protected Profile $profile, protected Permission $permission)
    {
    }

    public function permissions($idProfile)
    {

        $profile = $this->profile->find($idProfile);
        if(!$profile)
        {
            return redirect()->back();
        }
        $permissions = $profile->permissions()->paginate();
        return view('admin.pages.profile.permissions.index', compact('profile', 'permissions'));
    }


    public function profiles($idPermissions)
    {

        $permissions = $this->permission->find($idPermissions);
        if(!$permissions)
        {
            return redirect()->back();
        }
        $profiles = $permissions->profiles()->paginate();

        return view('admin.pages.permissions.profiles.index', compact('permissions', 'profiles'));
    }

    public function permissionsAvailableProfile(Request $request, $idProfile)
    {

        $profile = $this->profile->find($idProfile);

        if(!$profile)
        {
            return redirect()->back();
        }

        $filters = $request->except('_token');

        $permissions = $profile->permissionAvailable($request->filter);
        return view('admin.pages.profile.permissions.available', compact('profile', 'permissions', 'filters'));
    }




    public function attachPermissionProfile(attachPermissioRequest $request, $idProfile)
    {
        $profile = $this->profile->find($idProfile);

        if(!$profile)
        {
            return redirect()->back();
        }

        $profile->permissions()->attach($request->permission);

        return redirect()->route('profiles.permissions', $idProfile);

    }

    public function detachPermissionProfile( $idProfile, $idPermission)
    {
        $profile = $this->profile->find($idProfile);
        $permission = $this->permission->find($idPermission);

        if(!$profile || !$permission)
        {
            return redirect()->back();
        }

        $profile->permissions()->detach($permission); //Passando todo objeto

        return redirect()->route('profiles.permissions', $idProfile);

    }




}
