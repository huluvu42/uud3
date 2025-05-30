<?php
// app/Livewire/Admin/UserManagement.php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    public $users;
    public $showModal = false;
    public $editingUser = null;
    
    public $username = '';
    public $password = '';
    public $first_name = '';
    public $last_name = '';
    public $is_admin = false;
    public $can_reset_changes = false;

    public function mount()
    {
        $this->users = User::all();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editUser($userId)
    {
        $this->editingUser = User::find($userId);
        $this->username = $this->editingUser->username;
        $this->first_name = $this->editingUser->first_name;
        $this->last_name = $this->editingUser->last_name;
        $this->is_admin = $this->editingUser->is_admin;
        $this->can_reset_changes = $this->editingUser->can_reset_changes;
        $this->password = '';
        $this->showModal = true;
    }

    public function saveUser()
    {
        $this->validate([
            'username' => 'required|unique:users,username,' . ($this->editingUser ? $this->editingUser->id : 'NULL'),
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => $this->editingUser ? 'nullable' : 'required',
        ]);

        $data = [
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'is_admin' => $this->is_admin,
            'can_reset_changes' => $this->can_reset_changes,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUser) {
            $this->editingUser->update($data);
        } else {
            User::create($data);
        }

        $this->users = User::all();
        $this->closeModal();
        session()->flash('success', 'Benutzer gespeichert!');
    }

    public function deleteUser($userId)
    {
        User::find($userId)->delete();
        $this->users = User::all();
        session()->flash('success', 'Benutzer gelöscht!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingUser = null;
        $this->username = '';
        $this->password = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->is_admin = false;
        $this->can_reset_changes = false;
    }

    public function render()
    {
        return view('livewire.admin.user-management');
    }
}