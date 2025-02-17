<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $exception) {});
    }

    public function render($request, Throwable $exception)
    {
        if ($request->segment(1) == 'rest_api') {
            return response()->json(['message' => 'Server error', 'status' => 'error'], 500);
        }
        \Config::set('app.debug', false);
        if (! empty(session('user_id')) && (is_dev())) {
            \Config::set('app.debug', true);

            return parent::render($request, $exception);
        }

        return parent::render($request, $exception);
    }

    public function report(Throwable $exception)
    {
        if (! $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $erp = '';
            if (! empty(session('instance')) && ! empty(session('instance')->directory)) {
                $erp = session('instance')->directory;
            }
            $user_id = null;
            if (! empty(session('user_id'))) {
                $user_id = session('user_id');
            }
            if ($user_id && ! str_contains($exception->getMessage(), 'Connection was killed') && ! str_contains($exception->getMessage(), 'Access denied')) {
                $error = $exception->getMessage().' '.$exception->getFile().':'.$exception->getLine();
                $log = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'error_message' => $error,
                    'stack_trace' => $exception->getTraceAsString(),
                    'erp' => $erp,
                    'user_id' => $user_id,
                    'request_uri' => request()->getRequestUri(),
                ];

                exception_log($log);

                // try{
                //     \DB::connection('system')->table('erp_exception_log')->insert($log);
                //     // create_github_issue($error,$exception->getTraceAsString());
                // }catch(\Throwable $e){

                // }
            }
            parent::report($exception);
        }
    }
}
