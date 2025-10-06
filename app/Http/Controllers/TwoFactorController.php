<?php

namespace App\Http\Controllers;

use App\Http\Requests\Disable2FARequest;
use App\Http\Requests\Enable2FARequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;

class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:jwt');
    }

    /**
     * Generate 2FA QR code and secret
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     * @throws MissingQrCodeServiceException
     */
    public function generate(): JsonResponse
    {
        $user = auth()->user();

        if ($user->two_factor_enabled) {
            return jsonResponse(
                status: 400,
                message: '2FA is already enabled for your account'
            );
        }

        return transactional(function () use ($user) {
            $google2fa = new Google2FA();
            $secret = $google2fa->generateSecretKey();

            $user->two_factor_secret = $secret;
            $user->two_factor_enabled = false;
            $user->save();

            $qrCodeUrl = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->email,
                $secret
            );

            return jsonResponse(
                message: 'Scan this QR code with your authenticator app',
                data: [
                    'qr_code' => $qrCodeUrl,
                    'secret' => $secret,
                    'notice' => 'You must verify the code to enable 2FA',
                ]
            );
        });
    }

    /**
     * Enable 2FA after verifying the code
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function enable(Enable2FARequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->two_factor_enabled) {
            return jsonResponse(
                status: 400,
                message: '2FA is already enabled'
            );
        }

        if (!$user->two_factor_secret) {
            return jsonResponse(
                status: 400,
                message: 'No 2FA secret found. Please generate a QR code first.'
            );
        }

        return transactional(function () use ($user, $request) {
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

            if (!$valid) {
                return jsonResponse(
                    status: 400,
                    message: 'Invalid 2FA code. Please try again.'
                );
            }

            $user->two_factor_enabled = true;
            $user->two_factor_confirmed_at = now();
            $user->save();

            $recoveryCodes = $this->generateRecoveryCodes();
            // TODO: Save hashed recovery codes in another table

            return jsonResponse(
                message: '2FA enabled successfully',
                data: [
                    'recovery_codes' => $recoveryCodes,
                    'notice' => 'Save these recovery codes in a safe place. You will need them if you lose access to your authenticator app.',
                ]
            );
        });
    }

    /**
     * Disable 2FA after verifying password or code
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function disable(Disable2FARequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->two_factor_enabled) {
            return jsonResponse(
                status: 400,
                message: '2FA is not enabled'
            );
        }

        return transactional(function () use ($user, $request) {
            if ($request->has('password')) {
                if (!auth()->attempt([
                    'email' => $user->email,
                    'password' => $request->password,
                ])) {
                    return jsonResponse(
                        status: 400,
                        message: 'Invalid password'
                    );
                }
            } elseif ($request->has('code')) {
                $google2fa = new Google2FA();
                $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

                if (!$valid) {
                    return jsonResponse(
                        status: 400,
                        message: 'Invalid 2FA code'
                    );
                }
            }

            $user->two_factor_secret = null;
            $user->two_factor_enabled = false;
            $user->two_factor_confirmed_at = null;
            $user->save();

            // TODO: Delete recovery codes de la tabla

            return jsonResponse(
                message: '2FA disabled successfully'
            );
        });
    }

    /**
     * Get current 2FA status
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        return jsonResponse(
            data: [
                'enabled' => $user->two_factor_enabled,
                'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            ]
        );
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->two_factor_enabled) {
            return jsonResponse(
                status: 400,
                message: '2FA is not enabled'
            );
        }

        return transactional(function () {
            $recoveryCodes = $this->generateRecoveryCodes();
            // TODO: Save new hashed recovery codes

            return jsonResponse(
                message: 'Recovery codes regenerated successfully',
                data: [
                    'recovery_codes' => $recoveryCodes,
                    'notice' => 'Your old recovery codes are no longer valid.',
                ]
            );
        });
    }

    /**
     * Generate recovery codes
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(5) . '-' . Str::random(5));
        }

        return $codes;
    }
}
