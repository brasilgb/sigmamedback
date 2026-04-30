<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('is_admin', false)->count(),
            'active_subscriptions' => Tenant::where('sync_enabled', true)->count(),
            'total_revenue' => Payment::where('status', 'approved')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        $users = User::with(['tenants' => function ($query) {
            $query->withPivot('role');
        }])
            ->latest()
            ->paginate(10)
            ->through(function (User $user) {
                $user->setAttribute('user_type', $this->resolveUserType($user));

                return $user;
            });

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    protected function resolveUserType(User $user): string
    {
        if ($user->is_admin) {
            return 'Root';
        }

        return match ($user->tenants->first()?->account_usage) {
            'family' => 'Familiar/cuidador',
            'professional' => 'Profissional',
            default => 'Pessoal',
        };
    }

    public function payments()
    {
        $payments = Payment::with('tenant.owner')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/payments', [
            'payments' => $payments,
        ]);
    }

    public function toggleSync(Tenant $tenant)
    {
        $tenant->update([
            'sync_enabled' => ! $tenant->sync_enabled,
        ]);

        return back()->with('success', 'Sync status updated.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir a si mesmo.');
        }

        \DB::transaction(function () use ($user) {
            if (! $user->is_admin) {
                // Se não for root, exclui dados relacionados
                $user->profiles()->delete();
                Tenant::where('owner_id', $user->id)->delete();
            }

            // Se for root ou após excluir dados, exclui o usuário
            $user->delete();
        });

        return back()->with('success', 'Usuário e seus dados foram excluídos.');
    }
}
