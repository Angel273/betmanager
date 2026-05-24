<?php

namespace App\Livewire\Admin;

use App\Models\AllowedEmail;
use Livewire\Component;
use Livewire\WithPagination;

class AllowedEmailsManager extends Component
{
    use WithPagination;

    public $email = '';
    public $search = '';

    protected $updatesQueryString = ['search'];

    public function mount()
    {
        // Protect page: Only Admin allowed
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'Acceso no autorizado.');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addEmail()
    {
        $this->validate([
            'email' => 'required|email|unique:allowed_emails,email',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo ya está en la lista de permitidos.',
        ]);

        AllowedEmail::create([
            'email' => trim(strtolower($this->email)),
            'created_by' => auth()->user()->email,
        ]);

        $this->reset('email');
        session()->flash('success', 'Correo electrónico agregado con éxito.');
    }

    public function removeEmail($id)
    {
        $allowed = AllowedEmail::findOrFail($id);

        // Don't allow admin to remove their own email from allowed list to prevent lockouts
        if ($allowed->email === auth()->user()->email) {
            session()->flash('error', 'No puedes eliminar tu propio correo de la lista de permitidos.');
            return;
        }

        $allowed->delete();
        session()->flash('success', 'Correo electrónico eliminado de la lista de permitidos.');
    }

    public function render()
    {
        $emails = AllowedEmail::where('email', 'like', '%' . $this->search . '%')
            ->orderBy('email')
            ->paginate(10);

        return view('livewire.admin.allowed-emails-manager', [
            'emails' => $emails,
        ]);
    }
}
