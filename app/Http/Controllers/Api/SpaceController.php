<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Space::with('spaceType');

        if ($request->filled('type_id')) {
            $query->where('space_type_id', $request->type_id);
        }

        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->active();
        }

        return $this->successResponse($query->get(), 'Listado de espacios.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_type_id'  => 'required|exists:space_types,id',
            'name'           => 'required|string|max:255',
            'floor'          => 'required|string|max:100',
            'capacity'       => 'required|integer|min:1',
            'price_per_hour' => 'required|numeric|min:0',
            'is_active'      => 'boolean',
        ]);

        $space = Space::create($validated);

        return $this->successResponse($space->load('spaceType'), 'Espacio creado correctamente.', 201);
    }

    public function show(Space $space)
    {
        return $this->successResponse($space->load('spaceType'), 'Detalle del espacio.');
    }

    public function update(Request $request, Space $space)
    {
        $validated = $request->validate([
            'space_type_id'  => 'sometimes|exists:space_types,id',
            'name'           => 'sometimes|string|max:255',
            'floor'          => 'sometimes|string|max:100',
            'capacity'       => 'sometimes|integer|min:1',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'is_active'      => 'sometimes|boolean',
        ]);

        $space->update($validated);

        return $this->successResponse($space->load('spaceType'), 'Espacio actualizado correctamente.');
    }

    public function destroy(Space $space)
    {
        $space->delete();

        return $this->successResponse(null, 'Espacio eliminado correctamente.');
    }

    public function availability(Request $request, Space $space)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
        ]);

        $hasOverlap = $space->reservations()
            ->overlapping($request->start_time, $request->end_time)
            ->exists();

        return $this->successResponse([
            'space_id'     => $space->id,
            'space_name'   => $space->name,
            'is_active'    => $space->is_active,
            'is_available' => $space->is_active && !$hasOverlap,
        ], 'Consulta de disponibilidad realizada.');
    }
}