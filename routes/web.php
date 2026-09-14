<?php

use App\Http\Controllers\ProfileController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/projects/{project}', function (Project $project) {
        return view('projects.show', ['project' => $project]);
    })->middleware('can:view,project')->name('projects.show');

    Route::get('/attachments/{attachment}/download', function (Attachment $attachment) {
        abort_unless(
            auth()->id() === $attachment->task->project->user_id,
            403
        );

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    })->name('attachments.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';