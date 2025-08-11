<?php

namespace Ssbhattarai\MagicLink\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ssbhattarai\MagicLink\Services\MagicLinkService;

class MagicLinkController extends Controller
{
    protected $magicLinkService;

    public function __construct(MagicLinkService $magicLinkService)
    {
        $this->magicLinkService = $magicLinkService;
    }

    public function requestView()
    {
        return view('magiclink::magic-link');
    }

    public function requestLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $this->magicLinkService->sendMagicLink($request->email, $request);

            return back()->with('success', 'Magic link has been sent to your email!');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function login(string $token)
    {
        try {
            $user = $this->magicLinkService->loginWithToken($token);
            Auth::login($user);

            return redirect(config('magiclink.login_redirect'));
        } catch (\Exception $e) {
            abort(401, $e->getMessage());
        }
    }
}
