<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\Admin;
use App\Services\FileHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class DossierController extends Controller
{
    protected $fileHandler;

    /**
     * Create a new controller instance.
     */
    public function __construct(FileHandlerService $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        // Temporarily remove middleware for testing
        // $this->middleware('auth:client,admin');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Check if user is authenticated with client guard
        if (Auth::guard('client')->check()) {
            $dossiers = Dossier::where('client_id', Auth::guard('client')->id())->get();
        } 
        // Check if user is authenticated with admin guard
        elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Super admin can see all dossiers
            if ($admin->is_superadmin === 1) {
                $dossiers = Dossier::all();
            } 
            // Regular admin can only see their assigned dossiers
            else {
                $dossiers = Dossier::where('expert_id', $admin->id)->get();
            }
        } 
        else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($dossiers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agence' => 'required|string',
            'num_sinistre' => 'required|string',
            'date_sinistre' => 'required|date',
            'date_declaration' => 'required|date',
            'expert_nom' => 'nullable|string',
            'expert_id' => 'nullable|exists:admins,id',
            'assure_nom' => 'required|string',
            'num_police' => 'required|string',
            'compagnie' => 'required|string',
            'code_agence' => 'required|string',
            'num_chassis' => 'required|string',
            'matricule' => 'required|string',
            'annee' => 'required|integer',
            'categorie' => 'required|string',
            'date_debut_assurance' => 'required|date',
            'date_fin_assurance' => 'required|date',
            'carte_grise_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'declaration_recto_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'declaration_verso_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'tiers_nom' => 'required|string',
            'tiers_matricule' => 'required|string',
            'tiers_code_agence' => 'required|string',
            'tiers_num_police' => 'required|string',
            'tiers_compagnie' => 'required|string',
            'photos_accident' => 'required|array',
            'photos_accident.*' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'note_honoraire_montant' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except([
            'carte_grise_photo',
            'declaration_recto_photo',
            'declaration_verso_photo',
            'photos_accident'
        ]);

        $data['client_id'] = Auth::guard('client')->id();
        
        // Store files
        $data['carte_grise_photo'] = $this->fileHandler->storeFile(
            $request->file('carte_grise_photo'),
            "dossiers/{$data['client_id']}/documents"
        );

        $data['declaration_recto_photo'] = $this->fileHandler->storeFile(
            $request->file('declaration_recto_photo'),
            "dossiers/{$data['client_id']}/documents"
        );

        $data['declaration_verso_photo'] = $this->fileHandler->storeFile(
            $request->file('declaration_verso_photo'),
            "dossiers/{$data['client_id']}/documents"
        );

        $data['photos_accident'] = $this->fileHandler->storeFiles(
            $request->file('photos_accident'),
            "dossiers/{$data['client_id']}/accidents"
        );

        $dossier = Dossier::create($data);
        return response()->json($dossier, 201);
    }

    public function show($id)
    {
        $dossier = Dossier::findOrFail($id);
        
        // Check access permissions
        if (Auth::guard('client')->check()) {
            if ($dossier->client_id !== Auth::guard('client')->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Regular admin can only see their assigned dossiers
            if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($dossier);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type_vehicule' => 'sometimes|required|string',
            'is_physical' => 'sometimes|required|boolean',
            'agence' => 'sometimes|required|string',
            'num_sinistre' => 'sometimes|required|string',
            'date_sinistre' => 'sometimes|required|date',
            'num_dossier' => 'sometimes|required|string',
            'date_declaration' => 'sometimes|required|date',
            'expert_nom' => 'sometimes|nullable|string',
            'expert_id' => 'sometimes|nullable|exists:admins,id',
            'assure_nom' => 'sometimes|required|string',
            'num_police' => 'sometimes|required|string',
            'compagnie' => 'sometimes|required|string',
            'code_agence' => 'sometimes|required|string',
            'num_chassis' => 'sometimes|required|string',
            'matricule' => 'sometimes|required|string',
            'annee' => 'sometimes|required|integer',
            'mois' => 'sometimes|required|string',
            'categorie' => 'sometimes|required|string',
            'date_debut_assurance' => 'sometimes|required|date',
            'date_fin_assurance' => 'sometimes|required|date',
            'carte_grise_photo' => 'sometimes|required|string',
            'declaration_recto_photo' => 'sometimes|required|string',
            'declaration_verso_photo' => 'sometimes|required|string',
            'tiers_nom' => 'sometimes|required|string',
            'tiers_matricule' => 'sometimes|required|string',
            'tiers_code_agence' => 'sometimes|required|string',
            'tiers_num_police' => 'sometimes|required|string',
            'tiers_compagnie' => 'sometimes|required|string',
            'photos_accident' => 'sometimes|required|array',
            'link_pv' => Auth::guard('admin')->check() ? 'nullable|string' : 'prohibited',
            'link_note' => Auth::guard('admin')->check() ? 'nullable|string' : 'prohibited',
            'status' => 'sometimes|in:new,in_progress,ended,rejected',
            'admin_comment' => Auth::guard('admin')->check() ? 'nullable|string' : 'prohibited',
            'note_honoraire_montant' => Auth::guard('admin')->check() ? 'sometimes|nullable|string' : 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dossier = Dossier::findOrFail($id);
        
        // Check access permissions
        if (Auth::guard('client')->check()) {
            if ($dossier->client_id !== Auth::guard('client')->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Regular admin can only update their assigned dossiers
            if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $updateData = $request->all();

        // Only allow admin to update certain fields
        if (!Auth::guard('admin')->check()) {
            unset($updateData['link_pv']);
            unset($updateData['link_note']);
            unset($updateData['status']);
            unset($updateData['admin_comment']);
            unset($updateData['expert_id']);
            unset($updateData['note_honoraire_montant']);
        }

        // Only allow super admin to assign experts
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            if ($admin->role !== 'superadmin') {
                unset($updateData['expert_id']);
            }
        }

        // Mark adminchangeseen as false when admin updates the dossier
        if (Auth::guard('admin')->check()) {
            $updateData['adminchangeseen'] = false;
        }
        
        $dossier->update($updateData);
        return response()->json($dossier);
    }

    public function destroy($id)
    {
        $dossier = Dossier::findOrFail($id);
        
        // Check access permissions
        if (Auth::guard('client')->check()) {
            if ($dossier->client_id !== Auth::guard('client')->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Regular admin can only delete their assigned dossiers
            if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dossier->delete();
        return response()->json(null, 204);
    }

    /**
     * Get all admins - accessible by both clients and admins
     * Clients can see basic admin info for viewing assigned experts
     * Admins can see all admin info for assignment purposes
     */
    public function getAdmins()
    {
        if (!Auth::guard('client')->check() && !Auth::guard('admin')->check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Admin::query();

        if (Auth::guard('client')->check()) {
            $admins = $query->select('id', 'name', 'email', 'is_superadmin')->where('is_superadmin', false)->get();
        } 
        else {
            $admin = Auth::guard('admin')->user();
 
            $admins = $query->select('id', 'name', 'email', 'is_superadmin', 'created_at', 'updated_at')->get();
        }

        return response()->json($admins);
    }

    public function assignExpert(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:admins,id',
            'expert_nom' => 'nullable|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $dossier = Dossier::findOrFail($id);
        
        // Verify the expert exists and is an admin
        $expert = Admin::find($request->expert_id);
        if (!$expert || $expert->role === 'superadmin') {
            return response()->json(['error' => 'Invalid expert selected'], 400);
        }
    
        $dossier->update([
            'expert_id' => $request->expert_id,
            'expert_nom' => $request->expert_nom ?? $expert->name
        ]);
    
        return response()->json([
            'message' => 'Expert assigned successfully',
            'dossier' => $dossier->load('expert')
        ]);
    }
    
    /**
     * Get all dossiers with expert information (Super Admin only)
     */
    public function getDossiersWithExperts()
    {
        $dossiers = Dossier::with(['client', 'expert'])->get();
        
        return response()->json($dossiers);
    }
    
    /**
     * Get dossier statistics (Super Admin only)
     */
    public function getStatistics()
    {
        $stats = [
            'total_dossiers' => Dossier::count(),
            'by_status' => Dossier::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'assigned_dossiers' => Dossier::whereNotNull('expert_id')->count(),
            'unassigned_dossiers' => Dossier::whereNull('expert_id')->count(),
            'by_expert' => Dossier::with('expert')
                ->whereNotNull('expert_id')
                ->get()
                ->groupBy('expert.name')
                ->map(function ($group) {
                    return $group->count();
                }),
            'recent_dossiers' => Dossier::where('created_at', '>=', now()->subDays(7))->count()
        ];
    
        return response()->json($stats);
    }
    
    /**
     * Update dossier status with admin comment
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,in_progress,ended,rejected',
            'admin_comment' => 'nullable|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $dossier = Dossier::findOrFail($id);
        
        // Check if admin has access to this dossier
        $admin = Auth::guard('admin')->user();
        if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $dossier->update([
            'status' => $request->status,
            'admin_comment' => $request->admin_comment,
            'adminchangeseen' => false
        ]);
    
        return response()->json([
            'message' => 'Status updated successfully',
            'dossier' => $dossier
        ]);
    }
    
    /**
     * Add a comment to a dossier
     */
    public function addComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_comment' => 'required|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $dossier = Dossier::findOrFail($id);
        
        // Check if admin has access to this dossier
        $admin = Auth::guard('admin')->user();
        if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $existingComment = $dossier->admin_comment;
        $newComment = $request->admin_comment;
        
        // Append new comment with timestamp
        $timestamp = now()->format('Y-m-d H:i:s');
        $adminName = $admin->name;
        
        $updatedComment = $existingComment 
            ? $existingComment . "\n\n---\n[{$timestamp}] {$adminName}: {$newComment}"
            : "[{$timestamp}] {$adminName}: {$newComment}";
    
        $dossier->update([
            'admin_comment' => $updatedComment,
            'adminchangeseen' => false
        ]);
    
        return response()->json([
            'message' => 'Comment added successfully',
            'dossier' => $dossier
        ]);
    }


    public function addMontant(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note_honoraire_montant' => 'required|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $dossier = Dossier::findOrFail($id);
        
        // Check if admin has access to this dossier
        $admin = Auth::guard('admin')->user();
        if ($admin->role !== 'superadmin' && $dossier->expert_id !== $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $existingMontant = $dossier->note_honoraire_montant;
        $newMontant = $request->note_honoraire_montant;
        
        // Append new comment with timestamp
        $timestamp = now()->format('Y-m-d H:i:s');
        $adminName = $admin->name;
        
        $updatedMontant = $existingMontant 
            ? $existingMontant . "\n\n---\n[{$timestamp}] {$adminName}: {$newMontant}"
            : "[{$timestamp}] {$adminName}: {$newMontant}";
    
        $dossier->update([
            'note_honoraire_montant' => $updatedMontant,
            'adminchangeseen' => false
        ]);
    
        return response()->json([
            'message' => 'montant added successfully',
            'dossier' => $dossier
        ]);
    }

    /**
     * Get count of unseen dossiers by admin (GET)
     * Mark unseen dossiers as seen (POST)
     */
    public function seenAdmin(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Dossier::query();
        
        if ($admin->role !== 'superadmin') {
            $query->where('expert_id', $admin->id);
        }

        if ($request->isMethod('get')) {
            // GET: Return count of unseen dossiers
            $unseenCount = $query->where('seenbyadmin', false)->count();
            
            return response()->json([
                'unseen_count' => $unseenCount
            ]);
        } else {
            // POST: Mark all unseen dossiers as seen
            $affected = $query->where('seenbyadmin', false)
                           ->update(['seenbyadmin' => true]);
            
            return response()->json([
                'message' => 'Dossiers marked as seen',
                'affected_count' => $affected
            ]);
        }
    }

    /**
     * Get count of dossiers with admin changes (GET)
     * Mark dossiers as seen for admin changes (POST)
     */
    public function adminChange(Request $request)
    {
        $client = Auth::guard('client')->user();
    
        if (!$client) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $query = Dossier::where('client_id', $client->id);
    
        if ($request->isMethod('get')) {
            $unseenChangesCount = $query->where('adminchangeseen', false)->count();
    
            return response()->json([
                'unseen_changes_count' => $unseenChangesCount
            ]);
        }
    
        if ($request->isMethod('post')) {
            $affected = $query->where('adminchangeseen', false)
                              ->update(['adminchangeseen' => true]);
    
            return response()->json([
                'message' => 'Admin changes marked as seen',
                'affected_count' => $affected
            ]);
        }
    
        return response()->json(['error' => 'Invalid method'], 405);
    }
}