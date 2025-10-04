<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreUpdatePermission;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(protected Permission $repository)
    {
    }
    public function index()
    {
        $permissions = $this->repository->latest()->paginate(10);
        return view('admin.pages.permissions.index', compact('permissions'));
    }


    public function create()
    {
        return view('admin.pages.permissions.create');
    }

    public function store(StoreUpdatePermission $request)
    {
        $this->repository->create($request->all());
        return redirect()->route('permissions.index');

    }
    public function show($id)
    {
        $permission = $this->repository->findOrFail($id);
        return view('admin.pages.permissions.show', compact('permission'));
    }

    public function update(StoreUpdatePermission $request,  $id)
    {
        try {

            $permission = $this->repository->findOrFail($id);
            $permission->update($request->all());
            return redirect()->route('permissions.index');

        } catch (\Exception $e) {
            return response()->json(array('message' => 'Erro ao atualizar'), 500);
        }
    }

    public function edit($id)
    {
        $permission = $this->repository->findOrFail($id);

        if(!$permission)
        {
            return redirect()->back();

        }

        return view('admin.pages.permissions.edit', compact('permission'));

    }

    public function destroy($id)
    {
        $permission = $this->repository->findOrFail($id);

        if(!$permission)
        {
            return redirect()->back();

        }
        $this->repository->destroy($id);

        return redirect()->route('permissions.index');
    }

    public function search(Request $request)
    {
        $filters = $request->except('_token');

        $permission = $this->repository->search($request->filter);

        return view('admin.pages.permissions.index', ['permissions' => $permission, 'filters' => $filters] );
    }

}
