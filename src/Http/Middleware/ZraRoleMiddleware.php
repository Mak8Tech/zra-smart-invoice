<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ZraRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if the user has the required role
        // This implementation assumes the application has a way to check roles
        // Either via a roles relationship or a hasRole method
        if (!$this->userHasRole($user, $role)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access ZRA Smart Invoice functionality.');
        }

        return $next($request);
    }

    /**
     * Check if the user has the specified role
     *
     * @param  mixed  $user
     * @param  string  $role
     * @return bool
     */
    protected function userHasRole($user, string $role): bool
    {
        // Method 1: Check via hasRole method if it exists on the User model
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }
        
        // Method 2: Check via roles relationship if it exists
        if (method_exists($user, 'roles') && $user->roles) {
            return $user->roles->contains('name', $role);
        }
        
        // Method 3: Check permissions via ability (Laravel's Gate)
        if (method_exists($user, 'can')) {
            return $user->can($role);
        }
        
        // Default fallback (can be configured in the service provider)
        return in_array($user->email, config('zra.admin_emails', []));
    }
}
