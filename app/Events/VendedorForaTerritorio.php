<?php

namespace App\Events;

use App\Models\Localizacao;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendedorForaTerritorio
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $vendedor,
        public Localizacao $localizacao,
    ) {}
}
