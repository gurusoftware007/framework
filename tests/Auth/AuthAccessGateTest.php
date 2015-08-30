<?php

use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;

class GateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_gate_throws_exception_on_invalid_callback_type()
    {
        $this->getBasicGate()->define('foo', 'foo');
    }

    public function test_basic_closures_can_be_defined()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) { return true; });
        $gate->define('bar', function ($user) { return false; });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
    }

    public function test_current_user_that_is_on_gate_always_injected_into_closure_callbacks()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            $this->assertEquals(1, $user->id);

            return true;
        });

        $this->assertTrue($gate->check('foo'));
    }

    public function test_classes_can_be_defined_as_callbacks_using_at_notation()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', 'AccessGateTestClass@foo');

        $this->assertTrue($gate->check('foo'));
    }

    public function test_policy_classes_can_be_defined_to_handle_checks_for_given_type()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function test_policies_always_override_closures_with_same_name()
    {
        $gate = $this->getBasicGate();

        // Force failure if this is called...
        $gate->define('update', function () { $this->assertTrue(false); });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function test_for_user_method_attaches_a_new_user_to_a_new_gate_instance()
    {
        $gate = $this->getBasicGate();

        // Assert that the callback receives the new user with ID of 2 instead of ID of 1...
        $gate->define('foo', function ($user) {
            $this->assertEquals(2, $user->id);

            return true;
        });

        $this->assertTrue($gate->forUser((object) ['id' => 2])->check('foo'));
    }

    protected function getBasicGate()
    {
        return new Gate(new Container, function () { return (object) ['id' => 1]; });
    }
}

class AccessGateTestClass
{
    public function foo()
    {
        return true;
    }
}

class AccessGateTestDummy
{
    //
}

class AccessGateTestPolicy
{
    public function update($user, AccessGateTestDummy $dummy)
    {
        return $user instanceof StdClass;
    }
}
