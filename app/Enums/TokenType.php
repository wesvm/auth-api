<?php

namespace App\Enums;

enum TokenType: string
{
    case FORGOT_PASSWORD = 'forgot_password';
    case RESET_PASSWORD = 'reset_password';
    case EMAIL_VERIFICATION = 'email_verification';
    case BEARER = 'bearer';
    case MFA = 'mfa';
}
