<?php

namespace App\Events;

use App\Models\Visita;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitaConcluida
{
    use Dispatchable, SerializesModels;

    public function __construct(public Visita $visita) {}
}
