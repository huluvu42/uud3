<?php
// app/Livewire/Management/GroupManagement.php
namespace App\Livewire\Management;

use Livewire\Component;
use App\Models\Group;
use App\Models\Subgroup;
use App\Models\Stage;

class GroupManagement extends Component
{
    // Groups
    public $groups;
    public $showGroupModal = false;
    public $editingGroup = null;
    public $group_name = '', $group_backstage_day_1 = false, $group_backstage_day_2 = false;
    public $group_backstage_day_3 = false, $group_backstage_day_4 = false;
    public $group_voucher_day_1 = 0.0, $group_voucher_day_2 = 0.0;
    public $group_voucher_day_3 = 0.0, $group_voucher_day_4 = 0.0;
    public $group_remarks = '';

    // Subgroups
    public $subgroups;
    public $showSubgroupModal = false;
    public $editingSubgroup = null;
    public $subgroup_name = '', $subgroup_group_id = '';

    // Stages
    public $stages;
    public $showStageModal = false;
    public $editingStage = null;
    public $stage_name = '', $stage_presence_days = 'performance_day';
    public $stage_guest_allowed = false, $stage_vouchers_on_performance_day = 0.0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $currentYear = now()->year;
        $this->groups = Group::where('year', $currentYear)->with('subgroups')->get();
        $this->subgroups = Subgroup::with('group')->whereHas('group', function($q) use ($currentYear) {
            $q->where('year', $currentYear);
        })->get();
        $this->stages = Stage::where('year', $currentYear)->get();
    }

    // GROUP MANAGEMENT
    public function createGroup()
    {
        $this->resetGroupForm();
        $this->showGroupModal = true;
    }

    public function editGroup($groupId)
    {
        $this->editingGroup = Group::find($groupId);
        $this->group_name = $this->editingGroup->name;
        $this->group_backstage_day_1 = $this->editingGroup->backstage_day_1;
        $this->group_backstage_day_2 = $this->editingGroup->backstage_day_2;
        $this->group_backstage_day_3 = $this->editingGroup->backstage_day_3;
        $this->group_backstage_day_4 = $this->editingGroup->backstage_day_4;
        $this->group_voucher_day_1 = $this->editingGroup->voucher_day_1;
        $this->group_voucher_day_2 = $this->editingGroup->voucher_day_2;
        $this->group_voucher_day_3 = $this->editingGroup->voucher_day_3;
        $this->group_voucher_day_4 = $this->editingGroup->voucher_day_4;
        $this->group_remarks = $this->editingGroup->remarks;
        $this->showGroupModal = true;
    }

public function testClick()
{
    logger('TEST BUTTON CLICKED!');
    session()->flash('success', '🎉 TEST BUTTON FUNKTIONIERT!');
}

    public function saveGroup()
    {
        $this->validate([
            'group_name' => 'required|string|max:255',
            'group_voucher_day_1' => 'numeric|min:0|max:999.9',
            'group_voucher_day_2' => 'numeric|min:0|max:999.9',
            'group_voucher_day_3' => 'numeric|min:0|max:999.9',
            'group_voucher_day_4' => 'numeric|min:0|max:999.9',
        ]);

        $data = [
            'name' => $this->group_name,
            'backstage_day_1' => $this->group_backstage_day_1,
            'backstage_day_2' => $this->group_backstage_day_2,
            'backstage_day_3' => $this->group_backstage_day_3,
            'backstage_day_4' => $this->group_backstage_day_4,
            'voucher_day_1' => $this->group_voucher_day_1,
            'voucher_day_2' => $this->group_voucher_day_2,
            'voucher_day_3' => $this->group_voucher_day_3,
            'voucher_day_4' => $this->group_voucher_day_4,
            'remarks' => $this->group_remarks,
            'year' => now()->year,
        ];

        if ($this->editingGroup) {
            $this->editingGroup->update($data);
        } else {
            Group::create($data);
        }

        $this->loadData();
        $this->closeGroupModal();
        session()->flash('success', 'Gruppe gespeichert!');
    }

    public function deleteGroup($groupId)
    {
        Group::find($groupId)->delete();
        $this->loadData();
        session()->flash('success', 'Gruppe gelöscht!');
    }

    public function closeGroupModal()
    {
        $this->showGroupModal = false;
        $this->resetGroupForm();
    }

    private function resetGroupForm()
    {
        $this->editingGroup = null;
        $this->group_name = '';
        $this->group_backstage_day_1 = false;
        $this->group_backstage_day_2 = false;
        $this->group_backstage_day_3 = false;
        $this->group_backstage_day_4 = false;
        $this->group_voucher_day_1 = 0.0;
        $this->group_voucher_day_2 = 0.0;
        $this->group_voucher_day_3 = 0.0;
        $this->group_voucher_day_4 = 0.0;
        $this->group_remarks = '';
    }

    // SUBGROUP MANAGEMENT
    public function createSubgroup()
    {
        $this->resetSubgroupForm();
        $this->showSubgroupModal = true;
    }

    public function editSubgroup($subgroupId)
    {
        $this->editingSubgroup = Subgroup::find($subgroupId);
        $this->subgroup_name = $this->editingSubgroup->name;
        $this->subgroup_group_id = $this->editingSubgroup->group_id;
        $this->showSubgroupModal = true;
    }

    public function saveSubgroup()
    {
        $this->validate([
            'subgroup_name' => 'required|string|max:255',
            'subgroup_group_id' => 'required|exists:groups,id',
        ]);

        $data = [
            'name' => $this->subgroup_name,
            'group_id' => $this->subgroup_group_id,
        ];

        if ($this->editingSubgroup) {
            $this->editingSubgroup->update($data);
        } else {
            Subgroup::create($data);
        }

        $this->loadData();
        $this->closeSubgroupModal();
        session()->flash('success', 'Untergruppe gespeichert!');
    }

    public function deleteSubgroup($subgroupId)
    {
        Subgroup::find($subgroupId)->delete();
        $this->loadData();
        session()->flash('success', 'Untergruppe gelöscht!');
    }

    public function closeSubgroupModal()
    {
        $this->showSubgroupModal = false;
        $this->resetSubgroupForm();
    }

    private function resetSubgroupForm()
    {
        $this->editingSubgroup = null;
        $this->subgroup_name = '';
        $this->subgroup_group_id = '';
    }

    // STAGE MANAGEMENT
    public function createStage()
    {
        $this->resetStageForm();
        $this->showStageModal = true;
    }

    public function editStage($stageId)
    {
        $this->editingStage = Stage::find($stageId);
        $this->stage_name = $this->editingStage->name;
        $this->stage_presence_days = $this->editingStage->presence_days;
        $this->stage_guest_allowed = $this->editingStage->guest_allowed;
        $this->stage_vouchers_on_performance_day = $this->editingStage->vouchers_on_performance_day;
        $this->showStageModal = true;
    }

    public function saveStage()
    {
        $this->validate([
            'stage_name' => 'required|string|max:255',
            'stage_presence_days' => 'required|in:performance_day,all_days',
            'stage_vouchers_on_performance_day' => 'numeric|min:0|max:999.9',
        ]);

        $data = [
            'name' => $this->stage_name,
            'presence_days' => $this->stage_presence_days,
            'guest_allowed' => $this->stage_guest_allowed,
            'vouchers_on_performance_day' => $this->stage_vouchers_on_performance_day,
            'year' => now()->year,
        ];

        if ($this->editingStage) {
            $this->editingStage->update($data);
        } else {
            Stage::create($data);
        }

        $this->loadData();
        $this->closeStageModal();
        session()->flash('success', 'Bühne gespeichert!');
    }

    public function deleteStage($stageId)
    {
        Stage::find($stageId)->delete();
        $this->loadData();
        session()->flash('success', 'Bühne gelöscht!');
    }

    public function closeStageModal()
    {
        $this->showStageModal = false;
        $this->resetStageForm();
    }

    private function resetStageForm()
    {
        $this->editingStage = null;
        $this->stage_name = '';
        $this->stage_presence_days = 'performance_day';
        $this->stage_guest_allowed = false;
        $this->stage_vouchers_on_performance_day = 0.0;
    }

    public function render()
    {
        return view('livewire.management.group-management');
    }
}