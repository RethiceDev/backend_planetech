<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSessionGuard
{
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $actuelle  = hash('sha256', (string) $admin->mot_de_passe);
            $memorisee = $request->session()->get('admin_pw_hash');

            if ($memorisee === null) {
                // Première requête de cette session : on mémorise l'empreinte
                $request->session()->put('admin_pw_hash', $actuelle);
            } elseif (!hash_equals($memorisee, $actuelle)) {
                // Le mot de passe a changé depuis un autre appareil : on coupe cette session
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('admin.login')
                    ->with('error', 'Votre mot de passe a été modifié. Veuillez vous reconnecter.');
            }
        }

        return $next($request);
    }
}
