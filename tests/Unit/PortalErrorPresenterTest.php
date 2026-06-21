<?php

namespace Tests\Unit;

use App\Support\PortalErrorPresenter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalErrorPresenterTest extends TestCase
{
    #[Test]
    public function it_formats_null_column_errors_like_the_portal_dialog(): void
    {
        $presenter = new PortalErrorPresenter;
        $request = Request::create('/settings', 'PUT');
        $request->setRouteResolver(function () use ($request) {
            return (new Route('PUT', '/settings', []))->name('settings.update')->bind($request);
        });

        $exception = new QueryException(
            'mysql',
            'update organizations set country = ?',
            [null],
            new \Exception("SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'country' cannot be null")
        );

        $payload = $presenter->fromThrowable($exception, $request);

        $this->assertStringContainsString('Required field missing', $payload['title']);
        $this->assertSame('Updating company profile', $payload['action']);
        $this->assertSame("Column 'country' cannot be null", $payload['technical']);
        $this->assertStringContainsString('country', $payload['why']);
    }

    #[Test]
    public function it_formats_validation_errors_for_the_portal_dialog(): void
    {
        $presenter = new PortalErrorPresenter;
        $bag = new MessageBag(['company_name' => ['The company name field is required.']]);

        $payload = $presenter->fromMessageBag($bag);

        $this->assertStringContainsString('highlighted fields', $payload['title']);
        $this->assertStringContainsString('company name', $payload['technical']);
    }
}
