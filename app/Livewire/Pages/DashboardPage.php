<?php
namespace App\Livewire\Pages;

use Livewire\Component;

class DashboardPage extends Component
{

    public function render()
    {
        return view('pages.dashboard');
    }
}

/*
SYSTEM DESIGN:
Thin page shell: KPI strip + board are separate child components so each polls/re-renders independently.

DATA STRUCTURE:
Composition of 2 Livewire children.
*/
