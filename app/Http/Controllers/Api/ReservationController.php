<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Reservation::with(['space.spaceType', 'user']);

        if ($user->hasRole('client')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->successResponse($query->latest()->get(), 'Listado de reservas.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_id'   => 'required|exists:spaces,id',
            'start_time' => 'required|date|after:now',
            'end_time'   => 'required|date|after:start_time',
            'notes'      => 'nullable|string',
        ]);

        $user = $request->user();
        $space = Space::findOrFail($validated['space_id']);

        if (!$space->is_active) {
            return $this->errorResponse('El espacio seleccionado no está activo para reservas.', 422);
        }

        // Regla de negocio: máximo 2 reservas pending/confirmed simultáneas a futuro
        $activeFutureReservations = Reservation::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '>', now())
            ->count();

        if ($activeFutureReservations >= 2) {
            return $this->errorResponse('No puedes tener más de 2 reservas activas o pendientes a futuro.', 422);
        }

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $minutes = $start->diffInMinutes($end);

        if ($minutes < 60 || $minutes > 480) {
            return $this->errorResponse('La duración de la reserva debe ser entre 1 y 8 horas.', 422);
        }

        // Regla de negocio: validar solapamiento con scopeOverlapping
        $overlap = Reservation::where('space_id', $space->id)
            ->overlapping($start, $end)
            ->exists();

        if ($overlap) {
            return $this->errorResponse('El espacio ya está reservado en ese horario.', 409);
        }

        $reservation = Reservation::create([
            'user_id'    => $user->id,
            'space_id'   => $space->id,
            'start_time' => $start,
            'end_time'   => $end,
            'status'     => 'pending',
            'notes'      => $validated['notes'] ?? null,
        ]);

        return $this->successResponse(
            $reservation->load(['space.spaceType', 'user']),
            'Reserva creada correctamente.',
            201
        );
    }

    public function show(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        if ($user->hasRole('client') && $reservation->user_id !== $user->id) {
            return $this->errorResponse('No tienes permiso para ver esta reserva.', 403);
        }

        return $this->successResponse($reservation->load(['space.spaceType', 'user']), 'Detalle de la reserva.');
    }

    public function confirm(Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
            return $this->errorResponse('Solo se pueden confirmar reservas pendientes.', 422);
        }

        $reservation->update(['status' => 'confirmed']);

        return $this->successResponse($reservation, 'Reserva confirmada.');
    }

    public function reject(Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
            return $this->errorResponse('Solo se pueden rechazar reservas pendientes.', 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return $this->successResponse($reservation, 'Reserva rechazada.');
    }

    public function complete(Reservation $reservation)
    {
        if ($reservation->status !== 'confirmed') {
            return $this->errorResponse('Solo se pueden completar reservas confirmadas.', 422);
        }

        $reservation->update(['status' => 'completed']);

        return $this->successResponse($reservation, 'Reserva marcada como completada.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        if ($user->hasRole('client')) {
            if ($reservation->user_id !== $user->id) {
                return $this->errorResponse('Solo puedes cancelar tus propias reservas.', 403);
            }

            // Regla de negocio: cliente solo puede cancelar si start_time aún no ha pasado
            if (now()->greaterThanOrEqualTo($reservation->start_time)) {
                return $this->errorResponse('No puedes cancelar una reserva que ya inició o finalizó.', 422);
            }
        }

        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return $this->errorResponse('Esta reserva no puede ser cancelada en su estado actual.', 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return $this->successResponse($reservation, 'Reserva cancelada correctamente.');
    }
}