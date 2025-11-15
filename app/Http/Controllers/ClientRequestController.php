<?php

namespace App\Http\Controllers;

use App\Models\ClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_requests|unique:clients',
            'phone_number' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientRequest = ClientRequest::create($validator->validated());

        return response()->json([
            'message' => 'Request submitted successfully',
            'request' => $clientRequest
        ], 201);
    }

    public function index()
    {
        // Only superadmin can view all requests
        $requests = ClientRequest::orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,created,rejected',
            'admin_comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientRequest = ClientRequest::findOrFail($id);

        // If status is being set to 'created', create the client account
        if ($request->status === 'created' && $clientRequest->status !== 'created') {
            // Generate a random password
            $password = Str::random(10);

            $client = Client::create([
                'name' => $clientRequest->client_name,
                'email' => $clientRequest->email,
                'password' => Hash::make($password),
                'phone_number' => $clientRequest->phone_number,
                'company_name' => $clientRequest->company_name,
                'company_code' => $clientRequest->company_code
            ]);

            // Here you might want to send an email with the credentials
            // TODO: Implement email notification

            $clientRequest->update([
                'status' => 'created',
                'admin_comment' => $request->admin_comment
            ]);

            return response()->json([
                'message' => 'Client account created successfully',
                'client' => $client,
                'temporary_password' => $password // In production, this should be sent via email
            ]);
        }

        $clientRequest->update($validator->validated());

        return response()->json([
            'message' => 'Status updated successfully',
            'request' => $clientRequest
        ]);
    }

    public function show($id)
    {
        $clientRequest = ClientRequest::findOrFail($id);
        return response()->json($clientRequest);
    }
}
