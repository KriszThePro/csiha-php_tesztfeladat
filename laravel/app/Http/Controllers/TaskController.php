<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;

class TaskController extends Controller
{

  /**
   * Get a list of tasks
   * 
   * @param Request $request
   * 
   * Optional Body params:
   * - offset: int
   * - limit: int
   * - search: string
   * - sort_by: string
   * - order: string
   * 
   * @return Collection
   * 
   */
  public function list(Request $request): Collection
  {
    $validated = $request->validate([
      'offset' => 'integer|min:0',
      'limit' => "integer|between:0,{$this->max_records_in_query}",
      'search' => 'nullable|string|max:255',
      'sort_by' => 'nullable|string|in:user_name,created_at,estimated_time,used_time,completed_at', // created_at can be ambiguous
      'order' => 'nullable|string|in:asc,desc',
    ]);

    $offset = array_key_exists('offset', $validated) ? intval($validated['offset']) : 0;
    $limit = array_key_exists('limit', $validated) ? intval($validated['limit']) : 5;
    $search = array_key_exists('search', $validated) ? $validated['search'] : '';
    $sortBy = array_key_exists('sort_by', $validated) ? $validated['sort_by'] : null;
    $order = array_key_exists('order', $validated) ? $validated['order'] : 'asc';

    $tasks = DB::table('tasks')
      ->leftJoin('users', 'tasks.user_id', '=', 'users.id')
      ->select('tasks.*', 'users.name as user_name');

    if (!empty($search)) {
      $tasks = $tasks
        ->where(function ($query) use ($search) {
          $query->where('tasks.description', 'like', '%' . $search . '%')
            ->orWhere('users.name', 'like', '%' . $search . '%');
        });
    }

    if ($sortBy) {
      // Fix ambiguous column name
      if ($sortBy === 'user_name') {
        $tasks = $tasks->orderByRaw('users.name IS NULL ASC')->orderBy('users.name', $order);
      } else {
        $tasks = $tasks->orderByRaw('tasks.' . $sortBy . ' IS NULL ASC')->orderBy('tasks.' . $sortBy, $order);
      }
    }

    $tasks = $tasks->offset($offset)->limit($limit)->get();

    return $tasks;
  }

  /**
   * Get the count of tasks
   * 
   * @param Request $request
   * 
   * Optional Body params:
   * - search: string
   * 
   * @return JsonResponse
   * 
   */
  public function count(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'search' => 'nullable|string|max:255',
    ]);

    $search = array_key_exists('search', $validated) ? $validated['search'] : '';

    $count = DB::table('tasks')->count('id');
    return response()->json(['count' => $count]);
  }

  /**
   * Add a new task
   * 
   * @param Request $request
   * 
   * Required Body params
   * - description: string
   * - estimated_time: int
   * 
   * Optional Body params:
   * - user_id: int
   * 
   * @return JsonResponse
   * 
   */
  public function add(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'description' => 'required|string|max:255',
      'estimated_time' => 'required|integer|min:0',
      'user_id' => 'nullable|integer|min:0',
    ]);

    // If description and estimated_time are not present, return a bad request error
    if (!array_key_exists('description', $validated) || !array_key_exists('estimated_time', $validated)) {
      return response()->json(['error' => 'description and estimated_time are required'], 400);
    }

    $fields = [
      'description' => $validated['description'],
      'estimated_time' => $validated['estimated_time'],
    ];

    if (array_key_exists('user_id', $validated)) {
      $fields['user_id'] = $validated['user_id'];
    }

    $success = DB::table('tasks')->insert($fields);
    return response()->json(['success' => $success]);
  }

  /**
   * Edit a task
   * 
   * @param Request $request
   * @param int $id
   * 
   * Required Body params
   * - description: string
   * - estimated_time: int
   * 
   * Optional Body params:
   * - user_id: int
   * 
   * @return JsonResponse
   * 
   */
  public function edit(Request $request, int $id): JsonResponse
  {
    $validated = $request->validate([
      'description' => 'required|string|max:255',
      'estimated_time' => 'required|integer|min:0',
      'user_id' => 'nullable|integer|min:0',
    ]);

    $fields = [
      'description' => $validated['description'],
      'estimated_time' => $validated['estimated_time'],
    ];

    if (array_key_exists('user_id', $validated)) {
      $fields['user_id'] = $validated['user_id'];
    }

    $success = DB::table('tasks')->where('id', $id)->update($fields);
    return response()->json(['success' => $success]);
  }

  /**
   * Remove a task
   * 
   * @param Request $request
   * 
   * Required Body params
   * - id: int
   * 
   * @return JsonResponse
   * 
   */
  public function delete(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'id' => 'required|integer|min:1',
    ]);

    $success = DB::table('tasks')->where('id', $validated['id'])->delete();
    return response()->json(['success' => $success]);
  }

  /**
   * Remove many tasks
   * 
   * @param Request $request
   * 
   * Required Body params
   * - ids: array of integers
   * 
   * @return JsonResponse
   * 
   */
  public function deleteMany(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'integer|min:1',
    ]);

    $success = DB::table('tasks')->whereIn('id', $validated['ids'])->delete();
    return response()->json(['success' => $success]);
  }

  /**
   * Complete a task
   * 
   * @param Request $request
   * 
   * Required Body params
   * - id: int
   * 
   * @return JsonResponse
   * 
   */
  public function complete(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'id' => 'required|integer|min:1',
    ]);

    $success = DB::table('tasks')
      ->where('id', $validated['id'])
      ->update([
        'completed_at' => now(),
        'used_time' => DB::raw('TIMESTAMPDIFF(SECOND, created_at, NOW())')
      ]);
    return response()->json(['success' => $success]);
  }
}