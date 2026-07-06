<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\SubconAttachment;
use App\Support\SubconConstants as SC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence photos for deliveries and trips (proof of work, defects, Lalamove
 * receipts, proof of delivery). Stored as real files on the public disk.
 */
class SubconAttachmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'owner_type' => ['required', 'in:delivery,trip,po'],
            'owner_id'   => ['required', 'integer'],
        ]);

        $items = SubconAttachment::where('owner_type', $data['owner_type'])
            ->where('owner_id', $data['owner_id'])->orderBy('id')->get();

        return response()->json(['attachments' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'owner_type' => ['required', 'in:delivery,trip,po'],
            'owner_id'   => ['required', 'integer'],
            'photos'     => ['required', 'array', 'max:20'],
            'photos.*'   => ['image', 'max:8192'], // 8 MB each
        ]);

        $saved = [];
        foreach ($request->file('photos') as $file) {
            $path = $file->store('subcon/' . $data['owner_type'], 'public');
            $saved[] = SubconAttachment::create([
                'owner_type'    => $data['owner_type'],
                'owner_id'      => $data['owner_id'],
                'path'          => $path,
                'original_name' => substr((string) $file->getClientOriginalName(), 0, 255),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'uploaded_by'   => $request->user()->id,
            ]);
        }

        return response()->json(['attachments' => $saved], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $att = SubconAttachment::findOrFail($id);
        Storage::disk('public')->delete($att->path);
        $att->delete();

        return response()->json(['deleted' => true]);
    }
}
