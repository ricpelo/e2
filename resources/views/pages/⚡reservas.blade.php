<?php

use App\Models\Pista;
use App\Models\Reserva;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|exists:pistas,id')]
    public int $pista_id;

    public function mount()
    {
        $this->pista_id = Pista::first()->id;
    }

    #[Computed]
    public function pistas()
    {
        return Pista::all();
    }

    #[Computed]
    public function tablero()
    {
        $tablero = [];

        $lunes = now()->startOfWeek();

        for ($h = 10; $h < 20; $h++) {
            for ($d = 0; $d < 5; $d++) {
                $fecha = $lunes->copy()->addDays($d)->addHours($h)->format('Y-m-d H:i');
                $reserva = Reserva::where('pista_id', $this->pista_id)
                    ->where('fecha_hora', $fecha)
                    ->first();
                if ($reserva) {
                    if ($reserva->user_id == Auth::id()) {
                        $tablero[$fecha] = $reserva->id;
                    } else {
                        $tablero[$fecha] = 'O';
                    }
                } else {
                    $tablero[$fecha] = null;
                }
            }
        }

        return $tablero;
    }

    public function anularReserva($reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        Gate::authorize('anular-reserva', $reserva);

        $reserva->delete();
    }

    public function reservar($fecha)
    {
        $this->validate();

        try {
            $fecha = Carbon::createFromFormat('Y-m-d H:i', $fecha);

            // if ($fecha->lessThan(now())) {
            //     $this->addError('fecha', 'No se puede reservar para una fecha pasada.');
            //     return;
            // }

            if ($fecha->hour < 10 || $fecha->hour > 20) {
                $this->addError('fecha', 'Solo se pueden reservar horas entre las 10:00 y las 20:00.');
                return;
            }

            if ($fecha->isWeekend()) {
                $this->addError('fecha', 'No se pueden reservar los fines de semana.');
                return;
            }

            if ($fecha->weekOfYear() != now()->weekOfYear) {
                $this->addError('fecha', 'Solo se pueden reservar para la semana actual.');
                return;
            }

            if (Reserva::where('pista_id', $this->pista_id)
                    ->where('fecha_hora', $fecha)->exists()) {
                $this->addError('fecha', 'Esta hora ya está reservada para esa pista.');
                return;
            }

            Reserva::create([
                'pista_id' => $this->pista_id,
                'user_id' => Auth::id(),
                'fecha_hora' => $fecha,
            ]);
        } catch (InvalidFormatException $e) {
            $this->addError('fecha', 'Formato de fecha incorrecto.');
        }
    }
};
?>

<div>
    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <select class="select" wire:model.live="pista_id">
        @foreach ($this->pistas as $pista)
            <option value="{{ $pista->id }}">{{ $pista->nombre }}</option>
        @endforeach
    </select>

    <table class="table">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Lunes</th>
                <th>Martes</th>
                <th>Miércoles</th>
                <th>Jueves</th>
                <th>Viernes</th>
            </tr>
        </thead>
        <tbody>
            @for ($h = 10; $h < 20; $h++)
                <tr>
                    <td>{{ $h }}:00</td>
                    @for ($d = 0; $d < 5; $d++)
                        @php
                        $fecha = now()->startOfWeek()->copy()->addDays($d)->addHours($h);
                        $fechaStr = $fecha->format('Y-m-d H:i');
                        @endphp
                        <td>
                            @if ($this->tablero[$fechaStr] === 'O')
                                <span class="badge badge-error">Ocupado</span>
                            @elseif ($this->tablero[$fechaStr] !== null)
                                <button
                                    class="btn btn-xs btn-warning"
                                    wire:click="anularReserva({{ $this->tablero[$fechaStr] }})"
                                >
                                    Anular
                                </button>
                            @else
                                <button
                                    class="btn btn-xs btn-success"
                                    wire:click="reservar('{{ $fechaStr }}')"
                                >
                                    Reservar
                                </button>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
