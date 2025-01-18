<?php

namespace App\Enums;

enum TokenType: string
{
    case FORGOT_PASSWORD = 'forgot_password';
    case RESET_PASSWORD = 'reset_password';
    case VERIFY_EMAIL = 'verify_email';
    case BEARER = 'bearer';
    case MFA = 'mfa';
}
