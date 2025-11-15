<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SecureFileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:client,admin');
    }

    public function show($dossierId, $type)
    {
        $dossier = Dossier::findOrFail($dossierId);

        // Check if user has permission
        if (!Auth::guard('admin')->check() && 
            Auth::guard('client')->id() !== $dossier->client_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $path = match($type) {
            'pv' => $dossier->link_pv,
            'note' => $dossier->link_note,
            'carte_grise' => $dossier->carte_grise_photo,
            'declaration_recto' => $dossier->declaration_recto_photo,
            'declaration_verso' => $dossier->declaration_verso_photo,
            default => null
        };

        if (!$path) {
            return response()->json(['error' => 'File path not found'], 404);
        }

        try {
            if (!Storage::disk('private')->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $mimeType = Storage::disk('private')->mimeType($path);
            $content = Storage::disk('private')->get($path);

            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . basename($path) . '"');

        } catch (\Exception $e) {
            \Log::error('File access error: ' . $e->getMessage(), [
                'dossier' => $dossierId,
                'type' => $type,
                'path' => $path
            ]);
            return response()->json(['error' => 'Error accessing file'], 500);
        }
    }

    public function showAccidentPhoto($dossierId, $index)
    {
        $dossier = Dossier::findOrFail($dossierId);

        // Check if user has permission
        if (!Auth::guard('admin')->check() && 
            Auth::guard('client')->id() !== $dossier->client_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $photos = $dossier->photos_accident;
        
        if (!isset($photos[$index])) {
            return response()->json(['error' => 'Photo not found'], 404);
        }

        $path = $photos[$index];

        if (!Storage::disk('private')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->file(Storage::disk('private')->path($path));
    }

    public function uploadAdminDocs(Request $request, $dossierId)
    {
        // Verify admin access
        if (!Auth::guard('admin')->check()) {
            return response()->json(['error' => 'Unauthorized. Admin only.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'pv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'note_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dossier = Dossier::findOrFail($dossierId);

        if ($request->hasFile('pv_file')) {
            // Delete old PV if exists
            if ($dossier->link_pv) {
                Storage::disk('private')->delete($dossier->link_pv);
            }
            
            $dossier->link_pv = $request->file('pv_file')
                ->store("dossiers/{$dossierId}/admin", 'private');
        }

        if ($request->hasFile('note_file')) {
            // Delete old note if exists
            if ($dossier->link_note) {
                Storage::disk('private')->delete($dossier->link_note);
            }
            
            $dossier->link_note = $request->file('note_file')
                ->store("dossiers/{$dossierId}/admin", 'private');
        }

        $dossier->save();
        return response()->json($dossier);
    }
}