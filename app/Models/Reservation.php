<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['user_id', 'space_id', 'start_time', 'end_time', 'status', 'notes'];

    protected $appends = ['duration_hours', 'status_label'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    // Scope para detectar cruces de horario en reservas pending o confirmed
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            });
    }

    // Accessor: duración calculada en horas
    public function getDurationHoursAttribute()
    {
        return round(Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)) / 60, 2);
    }

    // Accessor: etiqueta legible en español
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            default => $this->status,
        };
    }
}