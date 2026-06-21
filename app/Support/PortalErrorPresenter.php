<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PortalErrorPresenter
{
    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'settings.update' => 'Updating company profile',
        'settings.index' => 'Viewing settings',
        'clients.store' => 'Adding a new client',
        'clients.update' => 'Updating client details',
        'clients.destroy' => 'Deleting a client',
        'tasks.store' => 'Creating a task',
        'tasks.update' => 'Updating a task',
        'invoices.store' => 'Creating an invoice',
        'invoices.update' => 'Updating an invoice',
        'staff.store' => 'Adding a team member',
        'staff.update' => 'Updating a team member',
        'subscriptions.store' => 'Creating a subscription',
        'subscriptions.update' => 'Updating a subscription',
        'credentials.store' => 'Saving a client password',
        'credentials.update' => 'Updating a client password',
        'register.organization' => 'Creating a workspace',
    ];

    public function fromThrowable(Throwable $e, ?Request $request = null): array
    {
        if ($e instanceof ValidationException) {
            return $this->fromMessageBag($e->validator->errors(), $request, $e);
        }

        if ($e instanceof QueryException) {
            return $this->fromQueryException($e, $request);
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $message = trim($e->getMessage()) ?: 'An unexpected error occurred.';

        return $this->build([
            'title' => $status === 403
                ? 'You do not have permission for this action.'
                : 'Something went wrong while processing your request.',
            'action' => $this->actionLabel($request),
            'problem' => $status === 403
                ? 'Your account is not allowed to perform this operation.'
                : 'The server could not complete the action. Try again or contact support if it persists.',
            'technical' => config('app.debug') ? $message : class_basename($e).($message ? ': '.$message : ''),
            'why' => config('app.debug')
                ? $message
                : 'An internal error occurred. Support can use the reference below.',
            'todo' => $status === 403
                ? 'Ask a partner in your firm to perform this action, or request access from your admin.'
                : 'Go back, check your entries, and try again. Use Copy details if you need to send this to support.',
            'reference' => $this->reference($request),
        ]);
    }

    public function fromMessageBag(MessageBag $errors, ?Request $request = null, ?Throwable $source = null): array
    {
        $lines = [];
        foreach ($errors->all() as $error) {
            $lines[] = '• '.$error;
        }

        $technical = implode("\n", $lines);
        $first = $errors->first();

        return $this->build([
            'title' => 'Please fix the highlighted fields and try again.',
            'action' => $this->actionLabel($request),
            'problem' => $first ?: 'One or more fields did not pass validation.',
            'technical' => $technical,
            'why' => 'The form was submitted with missing or invalid values.',
            'todo' => 'Review the fields marked in red on the form, correct them, and save again.',
            'reference' => $this->reference($request, $source),
        ]);
    }

    private function fromQueryException(QueryException $e, ?Request $request): array
    {
        $sqlMessage = $e->getMessage();
        $action = $this->actionLabel($request);

        if (preg_match("/Column '([^']+)' cannot be null/i", $sqlMessage, $m)) {
            $column = $m[1];
            $field = str_replace('_', ' ', $column);

            return $this->build([
                'title' => 'Required field missing — the database expected a value that was not sent.',
                'action' => $action,
                'problem' => 'Required field missing — the database expected a value that was not sent.',
                'technical' => "Column '{$column}' cannot be null",
                'why' => "A required database column received NULL or an empty value. Detail: Column '{$column}' cannot be null",
                'todo' => "Fill in {$field} (and any other mandatory fields on the form), then try again.",
                'reference' => $this->reference($request, $e),
            ]);
        }

        if (preg_match('/Duplicate entry/i', $sqlMessage)) {
            return $this->build([
                'title' => 'This record already exists.',
                'action' => $action,
                'problem' => 'A record with the same unique value already exists in your workspace.',
                'technical' => Str::limit($sqlMessage, 500),
                'why' => 'The database rejected the save because it would create a duplicate.',
                'todo' => 'Use a different value (email, code, or reference number) or edit the existing record.',
                'reference' => $this->reference($request, $e),
            ]);
        }

        if (preg_match('/foreign key constraint|Cannot add or update a child row/i', $sqlMessage)) {
            return $this->build([
                'title' => 'A linked record is missing or invalid.',
                'action' => $action,
                'problem' => 'The save failed because a related record could not be found.',
                'technical' => Str::limit($sqlMessage, 500),
                'why' => 'A foreign-key relationship in the database was violated.',
                'todo' => 'Refresh the page, re-select related items (client, service, user), and try again.',
                'reference' => $this->reference($request, $e),
            ]);
        }

        return $this->build([
            'title' => 'A database error prevented this save.',
            'action' => $action,
            'problem' => 'The database rejected the operation.',
            'technical' => Str::limit($sqlMessage, 500),
            'why' => 'The underlying SQL operation failed.',
            'todo' => 'Check all required fields and try again. Copy details for support if the problem continues.',
            'reference' => $this->reference($request, $e),
        ]);
    }

    /**
     * @param  array<string, string>  $parts
     * @return array<string, string>
     */
    private function build(array $parts): array
    {
        return [
            'title' => $parts['title'] ?? 'Something went wrong',
            'action' => $parts['action'] ?? 'Using the application',
            'problem' => $parts['problem'] ?? '',
            'technical' => $parts['technical'] ?? '',
            'why' => $parts['why'] ?? '',
            'todo' => $parts['todo'] ?? '',
            'reference' => $parts['reference'] ?? '',
        ];
    }

    private function actionLabel(?Request $request): string
    {
        if (! $request) {
            return 'Using the application';
        }

        $route = $request->route();
        $name = $route?->getName();

        if ($name && isset(self::ACTION_LABELS[$name])) {
            return self::ACTION_LABELS[$name];
        }

        if ($name) {
            return Str::headline(str_replace('.', ' ', $name));
        }

        return 'Working on '.Str::headline(trim($request->path(), '/'));
    }

    private function reference(?Request $request, ?Throwable $source = null): string
    {
        $parts = [
            'PE',
            now()->format('Ymd-His'),
            $request?->route()?->getName() ?? 'web',
        ];

        if ($source) {
            $parts[] = substr(sha1($source->getMessage()), 0, 8);
        }

        return strtoupper(implode('-', $parts));
    }

    public function toCopyText(array $payload): string
    {
        return implode("\n\n", array_filter([
            $payload['title'] ?? '',
            'WHAT YOU WERE DOING: '.($payload['action'] ?? ''),
            'PROBLEM: '.($payload['problem'] ?? ''),
            'TECHNICAL DETAIL: '.($payload['technical'] ?? ''),
            'WHY THIS HAPPENED: '.($payload['why'] ?? ''),
            'WHAT TO DO: '.($payload['todo'] ?? ''),
            'REFERENCE: '.($payload['reference'] ?? ''),
        ]));
    }
}
