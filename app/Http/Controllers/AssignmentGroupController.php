<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Pengelolaan kelompok tugas (bentuk kelompok): mahasiswa membentuk sendiri, dosen boleh mengatur. */
class AssignmentGroupController extends Controller
{
    use ChecksCourseAccess;

    /** Mahasiswa membuat kelompok & memilih anggota; dosen juga boleh membentuk. */
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $assignment->load('course');
        $this->ensureCourseAccess($request, $assignment->course);
        abort_unless($assignment->isGroup() && ! $assignment->isQuiz(), 404);

        $user = $request->user();
        $isDosen = $this->isOwnerDosen($user, $assignment);

        $data = $request->validate([
            'members' => ['nullable', 'array'],
            'members.*' => ['integer'],
        ]);

        $studentIds = $assignment->course->students()->pluck('users.id');
        $picked = collect($data['members'] ?? [])->map(fn ($i) => (int) $i);

        // Mahasiswa pembuat otomatis jadi anggota; dosen membentuk untuk mahasiswa lain.
        $memberIds = ($isDosen ? $picked : $picked->push($user->id))->unique()->values();

        if ($memberIds->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu anggota.');
        }
        if (! $memberIds->every(fn ($id) => $studentIds->contains($id))) {
            return back()->with('error', 'Ada anggota yang bukan peserta kelas ini.');
        }
        if (! $isDosen && $assignment->groupFor($user)) {
            return back()->with('error', 'Anda sudah tergabung dalam sebuah kelompok untuk tugas ini.');
        }
        if ($this->anyAlreadyGrouped($assignment, $memberIds->all())) {
            return back()->with('error', 'Ada anggota yang sudah tergabung dalam kelompok lain.');
        }
        if ($assignment->group_max && $memberIds->count() > $assignment->group_max) {
            return back()->with('error', "Maksimal {$assignment->group_max} anggota per kelompok.");
        }

        $n = $assignment->groups()->count() + 1;
        $group = $assignment->groups()->create(['name' => 'Kelompok '.$n, 'created_by' => $user->id]);
        $group->members()->sync($memberIds->all());

        return back()->with('status', 'Kelompok dibuat.');
    }

    /** Tambah satu anggota ke kelompok. */
    public function addMember(Request $request, AssignmentGroup $group): RedirectResponse
    {
        $assignment = $group->assignment()->with('course')->first();
        $this->ensureCourseAccess($request, $assignment->course);
        $this->ensureUnlocked($group);
        $this->authorizeManage($request, $group, $assignment);

        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $uid = (int) $data['user_id'];

        if (! $assignment->course->students()->whereKey($uid)->exists()) {
            return back()->with('error', 'Bukan peserta kelas ini.');
        }
        if ($this->anyAlreadyGrouped($assignment, [$uid])) {
            return back()->with('error', 'Mahasiswa itu sudah tergabung dalam kelompok lain.');
        }
        if ($assignment->group_max && $group->members()->count() >= $assignment->group_max) {
            return back()->with('error', "Kelompok sudah penuh (maks {$assignment->group_max}).");
        }

        $group->members()->syncWithoutDetaching([$uid]);

        return back()->with('status', 'Anggota ditambahkan.');
    }

    /** Keluarkan anggota (mahasiswa keluar sendiri, atau pembuat/dosen mengeluarkan). */
    public function removeMember(Request $request, AssignmentGroup $group, User $member): RedirectResponse
    {
        $assignment = $group->assignment()->with('course')->first();
        $this->ensureCourseAccess($request, $assignment->course);
        $this->ensureUnlocked($group);

        $user = $request->user();
        $isSelf = $member->id === $user->id;
        $canManage = $this->isOwnerDosen($user, $assignment) || $group->created_by === $user->id;
        abort_unless($isSelf || $canManage, 403);

        $group->members()->detach($member->id);

        // Kelompok kosong → bubarkan sekalian.
        if ($group->members()->count() === 0) {
            $this->deleteGroup($group);

            return back()->with('status', 'Kelompok dibubarkan (tidak ada anggota).');
        }

        return back()->with('status', $isSelf ? 'Anda keluar dari kelompok.' : 'Anggota dikeluarkan.');
    }

    /** Bubarkan kelompok. */
    public function destroy(Request $request, AssignmentGroup $group): RedirectResponse
    {
        $assignment = $group->assignment()->with('course')->first();
        $this->ensureCourseAccess($request, $assignment->course);
        $this->ensureUnlocked($group);

        $user = $request->user();
        abort_unless($this->isOwnerDosen($user, $assignment) || $group->created_by === $user->id, 403);

        $this->deleteGroup($group);

        return back()->with('status', 'Kelompok dibubarkan.');
    }

    // --- helpers ---

    private function isOwnerDosen(User $user, Assignment $assignment): bool
    {
        return $user->isDosen() && $assignment->course->user_id === $user->id;
    }

    /** Dosen pemilik atau anggota kelompok boleh mengelola keanggotaan. */
    private function authorizeManage(Request $request, AssignmentGroup $group, Assignment $assignment): void
    {
        $user = $request->user();
        $allowed = $this->isOwnerDosen($user, $assignment) || $group->hasMember($user->id);
        abort_unless($allowed, 403);
    }

    private function ensureUnlocked(AssignmentGroup $group): void
    {
        if ($group->isLocked()) {
            abort(403, 'Kelompok sudah dinilai — susunan tidak bisa diubah.');
        }
    }

    /** Apakah ada di antara $ids yang sudah tergabung di kelompok lain pada tugas ini. */
    private function anyAlreadyGrouped(Assignment $assignment, array $ids): bool
    {
        return $assignment->groups()
            ->whereHas('members', fn ($q) => $q->whereIn('users.id', $ids))
            ->exists();
    }

    private function deleteGroup(AssignmentGroup $group): void
    {
        // Hapus pengumpulan bersama (beserta berkasnya) bila ada.
        $sub = $group->submission()->first();
        if ($sub) {
            Storage::disk('public')->delete(array_values($sub->filePaths()));
            $sub->delete();
        }
        $group->members()->detach();
        $group->delete();
    }
}
