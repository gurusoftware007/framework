<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class RequestValidationTest extends TestCase
{
    public function testValidateMacro()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validate(['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateMacroWhenItFails()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/', 'GET', ['name' => null]);

        $request->validate(['name' => 'string']);
    }

    public function testValidateWithBagMacro()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validateWithBag('some_bag', ['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateWithBagMacroWhenItFails()
    {
        $request = Request::create('/', 'GET', ['name' => null]);

        try {
            $request->validateWithBag('some_bag', ['name' => 'string']);
        } catch (ValidationException $validationException) {
            $this->assertSame('some_bag', $validationException->errorBag);
        }
    }
}
