<?php

namespace App\Http\Controllers;

use App\Events\NoteCreated;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/notes",
     *     tags={"Notes"},
     *     summary="Get all notes for authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of notes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Buy groceries"),
     *                 @OA\Property(property="content", type="string", example="Milk, eggs, bread"),
     *                 @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="medium"),
     *                 @OA\Property(property="completed", type="boolean", example=false),
     *                 @OA\Property(property="due_date", type="string", format="date", example="2024-12-31", nullable=true),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"shopping", "food"}),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notes = Note::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notes);
    }

    /**
     * @OA\Post(
     *     path="/notes",
     *     tags={"Notes"},
     *     summary="Create a new note",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content"},
     *             @OA\Property(property="title", type="string", example="Buy groceries"),
     *             @OA\Property(property="content", type="string", example="Milk, eggs, bread"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="medium"),
     *             @OA\Property(property="completed", type="boolean", example=false),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-12-31", nullable=true),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"shopping", "food"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Note created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Buy groceries"),
     *             @OA\Property(property="content", type="string", example="Milk, eggs, bread"),
     *             @OA\Property(property="priority", type="string", example="medium"),
     *             @OA\Property(property="completed", type="boolean", example=false),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high',
            'completed' => 'sometimes|boolean',
            'due_date' => 'sometimes|nullable|date',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'string',
        ]);

        try {
            $user = $request->user();

            $note = Note::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->input('content'),
                'priority' => $request->priority ?? 'medium',
                'completed' => $request->completed ?? false,
                'due_date' => $request->due_date,
                'tags' => $request->tags,
            ]);

            Log::info('Note created successfully', [
                'note_id' => $note->id,
                'user_id' => $user->id,
                'title' => $note->title,
            ]);

            // Dispatch the event to notify admins
            event(new NoteCreated($note));

            return response()->json($note, 201);
        } catch (\Exception $e) {
            Log::error('Note creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/notes/{id}",
     *     tags={"Notes"},
     *     summary="Get a specific note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Note details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Buy groceries"),
     *             @OA\Property(property="content", type="string", example="Milk, eggs, bread"),
     *             @OA\Property(property="priority", type="string", example="medium"),
     *             @OA\Property(property="completed", type="boolean", example=false),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Note not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Note belongs to another user"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        // Check if the note belongs to the authenticated user
        if ($note->user_id !== $request->user()->id) {
            Log::warning('Unauthorized note access attempt', [
                'note_id' => $id,
                'note_owner_id' => $note->user_id,
                'requester_id' => $request->user()->id,
            ]);

            abort(403, 'Forbidden');
        }

        return response()->json($note);
    }

    /**
     * @OA\Put(
     *     path="/notes/{id}",
     *     tags={"Notes"},
     *     summary="Update a note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Buy groceries"),
     *             @OA\Property(property="content", type="string", example="Milk, eggs, bread, butter"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high"),
     *             @OA\Property(property="completed", type="boolean", example=true),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-12-31", nullable=true),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"shopping", "urgent"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Note updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="priority", type="string"),
     *             @OA\Property(property="completed", type="boolean"),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Note not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        // Check if the note belongs to the authenticated user
        if ($note->user_id !== $request->user()->id) {
            Log::warning('Unauthorized note update attempt', [
                'note_id' => $id,
                'note_owner_id' => $note->user_id,
                'requester_id' => $request->user()->id,
            ]);

            abort(403, 'Forbidden');
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'priority' => 'sometimes|in:low,medium,high',
            'completed' => 'sometimes|boolean',
            'due_date' => 'sometimes|nullable|date',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'string',
        ]);

        try {
            $note->update($request->only([
                'title',
                'content',
                'priority',
                'completed',
                'due_date',
                'tags',
            ]));

            Log::info('Note updated successfully', [
                'note_id' => $note->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json($note);
        } catch (\Exception $e) {
            Log::error('Note update failed', [
                'note_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/notes/{id}",
     *     tags={"Notes"},
     *     summary="Delete a note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Note deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Note deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Note not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        // Check if the note belongs to the authenticated user
        if ($note->user_id !== $request->user()->id) {
            Log::warning('Unauthorized note deletion attempt', [
                'note_id' => $id,
                'note_owner_id' => $note->user_id,
                'requester_id' => $request->user()->id,
            ]);

            abort(403, 'Forbidden');
        }

        try {
            $note->delete();

            Log::info('Note deleted successfully', [
                'note_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Note deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Note deletion failed', [
                'note_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
