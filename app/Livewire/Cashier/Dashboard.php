<?php

namespace App\Livewire\Cashier;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\PointTransaction;

class Dashboard extends Component
{
    public string $search = '';

    public function search(): void
    {
        $this->validate(['search' => 'required|min:9']);
        // Navigation to customer detail will go here
    }

    #[Layout('layouts.cashier')]
    public function render()
    {
        return view('livewire.cashier.dashboard', [
            'recentScans' => PointTransaction::with('customer')
                ->where('type', 'earn')
                ->whereDate('created_at', today())
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}
