<?php

namespace spec\Knp\MinibusBundle\Exception;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AlwaysRegisterStationExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Knp\MinibusBundle\Exception\AlwaysRegisterStationException');
    }

    function it_is_an_exception()
    {
        $this->shouldHaveType('Exception');
    }
}
