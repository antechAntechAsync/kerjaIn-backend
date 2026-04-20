<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Get all employees (professionals)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'professional');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $employees = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    /**
     * Get employee details
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employee = User::where('role', 'professional')->findOrFail($id);

        return response()->json([
            'data' => $employee,
        ]);
    }

    /**
     * Create new employee
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'line_manager' => 'nullable|exists:users,id',
            'seconde_line_manager' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'professional',
            'role_name' => 'Professional',
            'phone_number' => $request->phone_number,
            'position' => $request->position,
            'department' => $request->department,
            'line_manager' => $request->line_manager,
            'seconde_line_manager' => $request->seconde_line_manager,
            'status' => $request->status ?? 'active',
            'join_date' => now(),
        ]);

        return response()->json([
            'message' => 'Employee created successfully',
            'data' => $employee,
        ], 201);
    }

    /**
     * Update employee
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $employee = User::where('role', 'professional')->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($employee->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'line_manager' => 'nullable|exists:users,id',
            'seconde_line_manager' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        $updateData = [
            'name' => $request->name ?? $employee->name,
            'email' => $request->email ?? $employee->email,
            'phone_number' => $request->phone_number ?? $employee->phone_number,
            'position' => $request->position ?? $employee->position,
            'department' => $request->department ?? $employee->department,
            'line_manager' => $request->line_manager ?? $employee->line_manager,
            'seconde_line_manager' => $request->seconde_line_manager ?? $employee->seconde_line_manager,
            'status' => $request->status ?? $employee->status,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $employee->update($updateData);

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => $employee->fresh(),
        ]);
    }

    /**
     * Delete employee
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $employee = User::where('role', 'professional')->findOrFail($id);

        // Prevent deleting the current user
        if ($employee->id === $request->user()->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully',
        ]);
    }
}
