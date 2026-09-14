<?php

namespace App\Observers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentObserver
{
    public function deleting(Attachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->file_path);
    }
}