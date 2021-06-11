<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['messages' => ['email' => 'Already have an authenticated email.']], Response::HTTP_BAD_REQUEST);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(null, Response::HTTP_OK);
    }
}
