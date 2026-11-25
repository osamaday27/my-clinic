<?php
namespace App\Livewire\Pages;

use Livewire\Component;

class AppointmentsPage extends Component
{

    public function render()
    {
        return view('pages.appointments');
    }
}

/*
SYSTEM DESIGN:
Same board component with the date picker enabled (calendar & queue view).

DATA STRUCTURE:
Reuse of AppointmentsBoard (DRY).
*/
