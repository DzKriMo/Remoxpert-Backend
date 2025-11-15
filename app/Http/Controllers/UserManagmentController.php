<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Dossier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Routing\Controller;

class UserManagmentController extends Controller
{
    /**
     * Constructor with middleware to ensure only super admins can access these methods
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('superadmin');
    
    }

    /**
     * Create a new admin account
     */
    public function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'is_superadmin' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_superadmin' => $request->is_superadmin ?? false
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin
        ], 201);
    }

    /**
     * Create a new client account
     */
    public function createClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'code' => 'required|string|max:255|unique:clients',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'code' => $request->code,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Client created successfully',
            'client' => $client
        ], 201);
    }

  
    public function importUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'type' => 'required|in:admin,client'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        $headers = array_shift($data);
        
        $requiredFields = ['name', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!in_array($field, $headers)) {
                return response()->json([
                    'error' => "CSV file missing required field: {$field}",
                    'required_fields' => $requiredFields
                ], 422);
            }
        }
        
        $userType = $request->type;
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($data as $row) {
            if (count($row) !== count($headers)) {
                $failed++;
                continue;
            }

            $userData = array_combine($headers, $row);
            
            try {
                if ($userType === 'admin') {
                    $roleField = array_key_exists('role', $userData) ? $userData['role'] : null;
                    $isSuperAdmin = $roleField === 'superadmin';
                    
                    Admin::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make($userData['password']),
                        'is_superadmin' => $isSuperAdmin
                    ]);
                } else {
                    Client::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make($userData['password']),
                        // Add any additional client fields here
                    ]);
                }
                $created++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $userData,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => "Import completed. Created: {$created}, Failed: {$failed}",
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }
    
    /**
     * Get all admins
     */
    public function getAdmins()
    {
        $admins = Admin::where('id', '!=', auth()->guard('admin')->user()->id)->get();
        return response()->json($admins);
    }
    
    /**
     * Get all clients
     */
    public function getClients()
    {
        $clients = Client::all();
        return response()->json($clients);
    }

public function deleteUser(Request $request)
{
    $validator = Validator::make($request->all(), [
        'password' => 'required|string',
        'user_id' => 'required|integer',
        'user_type' => 'required|in:admin,client',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $superAdmin = Auth::guard('admin')->user();

    if (!$superAdmin->is_superadmin) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if (!Hash::check($request->password, $superAdmin->password)) {
        return response()->json(['error' => 'Incorrect password'], 403);
    }

    try {
        if ($request->user_type === 'admin') {
            if ($request->user_id == $superAdmin->id) {
                return response()->json(['error' => 'You cannot delete yourself'], 400);
            }
            Admin::findOrFail($request->user_id)->delete();
        } else {
            Client::findOrFail($request->user_id)->delete();
        }

        return response()->json(['message' => 'User deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Deletion failed: ' . $e->getMessage()], 500);
    }
}
    
    public function getClientStats()
    {
        try {
            // Get total number of clients
            $totalClients = Client::count();

            // Get number of active clients in last 7 days using the last_login_at field
            $activeClients = Client::where('last_login_at', '>=', Carbon::now()->subDays(7))
                ->count();

            return response()->json([
                'total_clients' => $totalClients,
                'active_clients_last_7_days' => $activeClients,
                'inactive_clients' => $totalClients - $activeClients,
                'updated_at' => Carbon::now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching client statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSystemStats()
    {
        try {
            $now = Carbon::now();
            $lastWeek = $now->copy()->subDays(7);
            $lastMonth = $now->copy()->subMonth();

            // Total users count
            $totalClients = Client::count();
            $totalAdmins = Admin::count();
            $totalUsers = $totalClients + $totalAdmins;

            // Active admins in last 7 days
            $activeAdmins = Admin::where('last_login_at', '>=', $lastWeek)->count();

            // System growth
            $newClientsLastWeek = Client::where('created_at', '>=', $lastWeek)->count();
            $newClientsLastMonth = Client::where('created_at', '>=', $lastMonth)->count();
            $newDossiersLastWeek = Dossier::where('created_at', '>=', $lastWeek)->count();
            $newDossiersLastMonth = Dossier::where('created_at', '>=', $lastMonth)->count();

            // System activity
            $totalDossiers = Dossier::count();
            $activeDossiers = Dossier::where('status', 'in_progress')->count();
            $completedDossiers = Dossier::where('status', 'ended')->count();
            $rejectedDossiers = Dossier::where('status', 'rejected')->count();

            // Calculate percentages
            $completionRate = $totalDossiers > 0 
                ? round(($completedDossiers / $totalDossiers) * 100, 2) 
                : 0;
            $rejectionRate = $totalDossiers > 0 
                ? round(($rejectedDossiers / $totalDossiers) * 100, 2) 
                : 0;

            return response()->json([
                'users' => [
                    'total_users' => $totalUsers,
                    'total_clients' => $totalClients,
                    'total_admins' => $totalAdmins,
                    'active_admins_last_7_days' => $activeAdmins,
                    'inactive_admins' => $totalAdmins - $activeAdmins
                ],
                'growth' => [
                    'new_clients' => [
                        'last_7_days' => $newClientsLastWeek,
                        'last_30_days' => $newClientsLastMonth,
                        'growth_rate_monthly' => $totalClients > 0 
                            ? round(($newClientsLastMonth / $totalClients) * 100, 2) 
                            : 0
                    ],
                    'new_dossiers' => [
                        'last_7_days' => $newDossiersLastWeek,
                        'last_30_days' => $newDossiersLastMonth
                    ]
                ],
                'activity' => [
                    'dossiers' => [
                        'total' => $totalDossiers,
                        'active' => $activeDossiers,
                        'completed' => $completedDossiers,
                        'rejected' => $rejectedDossiers,
                        'completion_rate' => $completionRate,
                        'rejection_rate' => $rejectionRate
                    ],
                    'average_completion_time' => $this->getAverageCompletionTime()
                ],
                'updated_at' => $now
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching system statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getAverageCompletionTime()
    {
        $completedDossiers = Dossier::where('status', 'ended')
            ->select([
                'created_at',
                'updated_at'
            ])
            ->get();

        if ($completedDossiers->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        foreach ($completedDossiers as $dossier) {
            $totalDays += $dossier->created_at->diffInDays($dossier->updated_at);
        }

        return round($totalDays / $completedDossiers->count(), 1);
    }


    public function forcePasswordReset(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:client,admin',
            'target_id' => 'required|integer',
            'new_password' => 'required|string|min:8|confirmed',
            'superadmin_password' => 'required|string'
        ]);

        $superadmin = auth('admin')->user();
        if (!$superadmin || !$superadmin->is_superadmin || !Hash::check($request->superadmin_password, $superadmin->password)) {
            return response()->json(['error' => 'Superadmin password is incorrect'], 403);
        }

        $model = $request->target_type === 'client' ? Client::class : Admin::class;
        $user = $model::findOrFail($request->target_id);

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}