<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logError($e);
        });
    }

    /**
     * Log error with enhanced context information
     */
    protected function logError(Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        // Add request data for non-GET requests
        if (!request()->isMethod('GET')) {
            $context['request_data'] = request()->except(['password', 'password_confirmation', 'current_password']);
        }

        // Log to error tracking channel
        Log::channel('error_tracking')->error('Application Exception', $context);

        // Log critical errors to multiple channels
        if ($this->isCriticalError($exception)) {
            Log::channel('slack')->critical('Critical Application Error', $context);
            
            // Send email notification for critical errors in production
            if (app()->environment('production')) {
                $this->notifyCriticalError($exception, $context);
            }
        }
    }

    /**
     * Determine if an exception is critical
     */
    protected function isCriticalError(Throwable $exception): bool
    {
        $criticalExceptions = [
            \Illuminate\Database\QueryException::class,
            \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException::class,
            \Exception::class, // Generic exceptions that might indicate system issues
        ];

        foreach ($criticalExceptions as $criticalException) {
            if ($exception instanceof $criticalException) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send critical error notification
     */
    protected function notifyCriticalError(Throwable $exception, array $context): void
    {
        try {
            // Log the notification attempt
            Log::channel('error_tracking')->info('Sending critical error notification', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            // Here you would integrate with your notification service
            // For now, we'll just log it as a placeholder
            Log::channel('emergency')->emergency('CRITICAL ERROR NOTIFICATION', $context);
        } catch (Throwable $e) {
            // Prevent notification failures from causing additional exceptions
            Log::channel('emergency')->error('Failed to send critical error notification', [
                'original_exception' => get_class($exception),
                'notification_exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Log security-related exceptions
        if ($this->isSecurityException($exception)) {
            Log::channel('security')->warning('Security Exception', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);
        }

        return parent::render($request, $exception);
    }

    /**
     * Determine if an exception is security-related
     */
    protected function isSecurityException(Throwable $exception): bool
    {
        $securityExceptions = [
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
            \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class,
            \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class,
        ];

        foreach ($securityExceptions as $securityException) {
            if ($exception instanceof $securityException) {
                return true;
            }
        }

        return false;
    }
}