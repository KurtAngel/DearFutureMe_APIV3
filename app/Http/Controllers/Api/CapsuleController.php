<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Capsule;
use Illuminate\Http\Request;
use App\Models\ReceivedCapsule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\CapsuleResource;
use App\Models\images;
use Faker\Provider\Image;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class CapsuleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum')
        ];
    }
    public function send(Request $request, ReceivedCapsule $capsule) {
        
        // Gate::authorize('modify', $capsule);

        $capsule = $request->validate([
            'title' => 'required|max:50|string',
            'message' => 'required|max:500|string',
            'content' => 'nullable',
            'receiver_email' => 'required',
            'scheduled_open_at' => 'required'
        ]);

        if(!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $createdCapsule = $request->user()->receivedCapsule()->create(array_merge($capsule));

        return response()->json([
            'data' => $createdCapsule,
            'message' => 'Capsule sent Successfully'
        ]);
    }

    public function index() {

        //check authenticated user
        $user = Auth::user();
        
        $capsules = Capsule::where('user_id', $user->id)->get();
        
        if($capsules->isEmpty()) {
            return response()->json(['message' => 'No capsules found!'], 404);
        } else {
            return CapsuleResource::collection($capsules);
        } 
    }

    public function show(Capsule $capsule) {

        $user = Auth::user();
    
        // Check if the capsule belongs to the authenticated user
        if ($capsule->user_id !== $user->id) {
            return response()->json(['message' => 'Capsule not found!'], 404);
        }

        // Return the capsule as a resource
        return response()->json([
            'id'=> $capsule['id'],
            'title'=> $capsule['title'],
            'message'=> $capsule['message'],
            'content'=> $capsule['content'],
            'receiver_email'=> $capsule['receiver_email'],
            'schedule_open_at'=> $capsule['schedule_open_at']
        ], 200);
    }

    public function view(ReceivedCapsule $receivedCapsule) {

        // Gate::authorize('modify_receiver', $received_capsule);

        $user = Auth::user();

        if ($receivedCapsule->receiver_email !== $user->email) {
            return response()->json(['message' => 'You do not own this capsule'], 404);
        }

        return response()->json([
            'Info' => $receivedCapsule
        ], 200);
    }

    public function destroy(Capsule $capsule) {

        Gate::authorize('modify', $capsule);
        
        if (!$capsule) {
            return response()->json(['message' => 'Capsule not found'], 404);
        }
    
            // Delete the specific capsule
            $capsule->delete();
    
            return response()->json(['message' => 'Capsule deleted successfully'], 200);
    }

    public function store(Request $request) {
        // Validate incoming request data
        $validatedData = $request->validate([
            'title' => 'required|max:50|string',
            'message' => 'required|max:500|string',
            'receiver_email' => 'nullable|email',
            'scheduled_open_at' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $imagePath = $request->get('images', 'public');
    
        // Optionally check if receiver_email exists in users table
        if (isset($validatedData['receiver_email'])) {
            $receiver = User::where('email', $validatedData['receiver_email'])->first();
            
            if (!$receiver) {
                return response()->json(['message' => 'Receiver not found.'], 404);
            }
        }
    
        // Create the capsule for the authenticated user
        $capsule = $request->user()->capsules()->create($validatedData);

            // Handle image upload if an image is provided
    if ($request->hasFile('image')) {
        // Store the image and get the path
        $imageFile = $request->file('image');
        $imagePath = $imageFile->store('images', 'public'); // Store in storage/app/public/images

        // Create a new image record and associate it with the capsule
        $image = new images([
            'images' => $imagePath,
            'capsule_type' => get_class($capsule), // Set the capsule type
            'capsule_id' => $capsule->id
        ]);

        // Assuming you have a morphMany relationship defined in the Capsule model
        $capsule->images()->save($image);
    }
    
        return response()->json([
            'info' => $capsule,
            'draft' => 'Capsule has been moved to draft',
            'image' => $imagePath
        ], 200);
    }
    
    public function update(Request $request, Capsule $capsule) {

        Gate::authorize('modify', $capsule);

        // Check if the capsule exists
        if (!$capsule) {
            return response()->json(['message' => 'Capsule not found'], 404);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|max:50|string',
            'message' => 'required|max:500|string'
        ]);

        // Update the capsule with the validated data

        $capsule->update($validatedData);

        return response()->json([
            'id'=> $capsule['id'],
            'title'=> $capsule['title'],
            'message'=> $capsule['message'],
            'content'=> $capsule['content'],
            'receiver_email'=> $capsule['receiver_email'],
            'schedule_open_at'=> $capsule['schedule_open_at'],
            'messageResponse'=> 'Updated Successfully'
        ], 200);
        }
}
