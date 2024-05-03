<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
  /**
   * Get a list of users
   * 
   * @param Request $request
   * 
   * Optional Body params:
   * - limit: int
   * - name: string
   * 
   * @return Collection
   * 
   */
  public function list(Request $request): Collection
  {
    $validated = $request->validate([
      'limit' => "integer|between:0,{$this->max_records_in_query}",
      'name' => 'nullable|string|max:255',
    ]);

    $limit = array_key_exists('limit', $validated) ? intval($validated['limit']) : 5;
    $name = array_key_exists('name', $validated) ? $validated['name'] : '';

    $users = DB::table('users')
      ->select('id', 'name', 'email');

    if (!empty($name)) {
      $users = $users->where('name', 'like', '%' . $name . '%');
    }

    $users = $users->limit($limit)->get();

    return $users;
  }
}