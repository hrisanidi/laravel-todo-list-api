<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/users",
     *     tags={"Admin"},
     *     summary="Get all users (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="is_admin", type="boolean", example=false),
     *                 @OA\Property(property="notes_count", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function users(Request $request)
    {
        $users = User::withCount('notes')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'notes_count' => $user->notes_count,
                    'created_at' => $user->created_at,
                ];
            });

        Log::info('Admin accessed users list', [
            'admin_id' => $request->user()->id,
            'total_users' => $users->count(),
        ]);

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *     path="/admin/users/{userId}/notes",
     *     tags={"Admin"},
     *     summary="Get all notes for a specific user (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user's notes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Buy groceries"),
     *                 @OA\Property(property="content", type="string", example="Milk, eggs, bread"),
     *                 @OA\Property(property="priority", type="string", example="medium"),
     *                 @OA\Property(property="completed", type="boolean", example=false),
     *                 @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function userNotes(Request $request, $userId)
    {
        User::findOrFail($userId);

        $notes = Note::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Admin accessed user notes', [
            'admin_id' => $request->user()->id,
            'target_user_id' => $userId,
            'notes_count' => $notes->count(),
        ]);

        return response()->json($notes);
    }
}
