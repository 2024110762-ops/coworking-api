<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpaceType;
use Illuminate\Http\Request;

class SpaceTypeController extends Controller
{
    public function index()
    {
        return $this->successResponse(SpaceType::all(), 'Tipos de espacio obtenidos.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|unique:space_types,name',
            'description' => 'nullable|string',
        ]);

        $type = SpaceType::create($validated);

        return $this->successResponse($type, 'Tipo de espacio creado.', 201);
    }

    public function update(Request $request, SpaceType $spaceType)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|unique:space_types,name,' . $spaceType->id,
            'description' => 'nullable|string',
        ]);

        $spaceType->update($validated);

        return $this->successResponse($spaceType, 'Tipo de espacio actualizado.');
    }

    public function destroy(SpaceType $spaceType)
    {
        $spaceType->delete();

        return $this->successResponse(null, 'Tipo de espacio eliminado.');
    }
}