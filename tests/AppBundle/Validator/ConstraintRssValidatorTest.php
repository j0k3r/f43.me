<?php

namespace Tests\AppBundle\Validator;

use AppBundle\Validator\Constraints\ConstraintRss;
use AppBundle\Validator\Constraints\ConstraintRssValidator;
use GuzzleHttp\Psr7\Response;
use Tests\AppBundle\AppTestCase;

class ConstraintRssValidatorTest extends AppTestCase
{
    public function testValidatorValid()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->never())
            ->method('addViolation');

        $client = self::getMockClient([(new Response(200, [], 'This is a valid'))]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFail()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo('Feed "%string%" is not valid.'),
                $this->equalTo(['%string%' => 'http://0.0.0.0'])
            );

        $client = self::getMockClient([(new Response(200, [], 'This is a not valid'))]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailFirst()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo('Feed "%string%" is not valid.'),
                $this->equalTo(['%string%' => 'http://0.0.0.0'])
            );

        $client = self::getMockClient([
            (new Response(400, [], 'oops')),
            (new Response(200, [], 'This is a not valid')),
        ]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailTwice()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->never())
            ->method('addViolation');

        $client = self::getMockClient([
            (new Response(400, [], 'oops')),
            (new Response(400, [], 'oops')),
        ]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }
}
