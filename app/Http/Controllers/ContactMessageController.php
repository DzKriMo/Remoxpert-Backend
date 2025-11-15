<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contact = ContactMessage::create($validator->validated());

        return response()->json([
            'message' => 'Message sent successfully',
            'contact' => $contact
        ], 201);
    }

    public function destroy($id)
    {
        $message = ContactMessage::find($id);
        
        if (!$message) {
            return response()->json(['message' => 'Contact message not found'], 404);
        }

        $message->delete();

        return response()->json(['message' => 'Contact message deleted successfully'], 200);
    }

    public function index()
    {
        // Only superadmin should access this
        $messages = ContactMessage::orderBy('created_at', 'desc')->get();
        return response()->json($messages);
    }

    public function passwordResetRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'client_name' => 'nullable|string|max:255',
            'message' => 'nullable|string'
        ]);

        $contact = ContactMessage::create([
            'client_name' => $request->client_name ?? '',
            'email' => $request->email,
            'subject' => 'password_reset',
            'message' => $request->message ?? ''
        ]);

        return response()->json([
            'message' => 'Password reset request sent successfully',
            'contact' => $contact
        ], 201);
    }
}
