<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

trait VerifiesEmails
{
    use RedirectsUsers;

    /**
     * Show the email verification notice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                        ? redirect($this->redirectPath())
                        : view('auth.verify');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        if ($request->route('id') == $request->user()->getKey() &&
            $request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->sendVerifyResponse($request);
    }

    /**
     * Get the response for a successful verify.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function sendVerifyResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => trans('auth.verified')]);
        }

        return redirect($this->redirectPath())->with('verified', true);
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->sendAlreadyVerifiedResponse($request);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->sendResendResponse($request);
    }

    /**
     * Get the response for a already email address verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function sendAlreadyVerifiedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => trans('auth.already_verified')]);
        }

        return redirect($this->redirectPath());
    }

    /**
     * Get the response for a successful resend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function sendResendResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => trans('auth.resent')]);
        }

        return back()->with('resent', true);
    }
}
