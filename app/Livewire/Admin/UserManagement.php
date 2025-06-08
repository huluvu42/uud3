<?php
// app/Livewire/Admin/UserManagement.php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    public $search = '';
    public $searchResults = [];
    public $showModal = false;
    public $editingUser = null;

    public $username = '';
    public $password = '';
    public $first_name = '';
    public $last_name = '';
    public $is_admin = false;
    public $can_reset_changes = false;
    public $can_manage = false; // Neues Feld hinzugefügt

    public function mount()
    {
        // Initial load - show all users
        $this->searchResults = User::orderBy('first_name')->orderBy('last_name')->get();
    }

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->searchUsers();
        } else {
            // Show all users when search is cleared
            $this->searchResults = User::orderBy('first_name')->orderBy('last_name')->get();
        }
    }

    public function searchUsers()
    {
        $this->searchResults = User::where(function ($query) {
            $query->where('first_name', 'ILIKE', '%' . $this->search . '%')
                ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%')
                ->orWhere('username', 'ILIKE', '%' . $this->search . '%');
        })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function selectUserForEdit($userId)
    {
        $this->editingUser = User::find($userId);
        if (!$this->editingUser) {
            session()->flash('error', 'Benutzer nicht gefunden!');
            return;
        }

        $this->username = $this->editingUser->username;
        $this->first_name = $this->editingUser->first_name;
        $this->last_name = $this->editingUser->last_name;
        $this->is_admin = $this->editingUser->is_admin;
        $this->can_reset_changes = $this->editingUser->can_reset_changes;
        $this->can_manage = $this->editingUser->can_manage; // Neues Feld hinzugefügt
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

        // Zusätzliche Validierung: Admin-Benutzer darf nicht entadminisiert werden
        if ($this->editingUser && $this->editingUser->isProtectedAdmin() && !$this->is_admin) {
            session()->flash('error', 'Der Admin-Benutzer muss Administrator bleiben!');
            return;
        }

        $data = [
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'is_admin' => $this->is_admin,
            'can_reset_changes' => $this->can_reset_changes,
            'can_manage' => $this->can_manage, // Neues Feld hinzugefügt
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        try {
            if ($this->editingUser) {
                $this->editingUser->update($data);
                session()->flash('success', 'Benutzer aktualisiert!');
            } else {
                User::create($data);
                session()->flash('success', 'Benutzer erstellt!');
            }

            // Refresh search results
            $this->updatedSearch();
            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    public function deleteUser($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                session()->flash('error', 'Benutzer nicht gefunden!');
                return;
            }

            // Prüfen ob Benutzer gelöscht werden kann
            if (!$user->canBeDeleted()) {
                session()->flash('error', 'Der Admin-Benutzer kann nicht gelöscht werden!');
                return;
            }

            // Zusätzliche Sicherheit: Eigenen Account nicht löschen
            if ($user->id === auth()->id()) {
                session()->flash('error', 'Sie können Ihren eigenen Account nicht löschen!');
                return;
            }

            $user->delete();
            $this->updatedSearch(); // Refresh search results
            session()->flash('success', 'Benutzer "' . $user->username . '" wurde gelöscht!');
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Löschen: ' . $e->getMessage());
            \Log::error('User deletion error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'admin_user' => auth()->id()
            ]);
        }
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
        $this->can_manage = false; // Neues Feld hinzugefügt
    }

    public function render()
    {
        return view('livewire.admin.user-management');
    }
}
