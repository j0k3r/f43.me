<?php

namespace App\Tests\Validator;

use App\Tests\AppTestCase;
use App\Validator\Constraints\ConstraintRss;
use App\Validator\Constraints\ConstraintRssValidator;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConstraintRssValidatorTest extends AppTestCase
{
    public function testValidatorValid(): void
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->never())
            ->method('addViolation');

        $client = self::getMockClient([new Response(200, [], 'This is a valid')]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFail(): void
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo('Feed "%string%" is not valid.'),
                $this->equalTo(['%string%' => 'http://0.0.0.0'])
            );

        $client = self::getMockClient([new Response(200, [], 'This is a not valid')]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailFirst(): void
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo('Feed "%string%" is not valid.'),
                $this->equalTo(['%string%' => 'http://0.0.0.0'])
            );

        $client = self::getMockClient([
            new Response(400, [], 'oops'),
            new Response(200, [], 'This is a not valid'),
        ]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailTwice(): void
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->never())
            ->method('addViolation');

        $client = self::getMockClient([
            new Response(400, [], 'oops'),
            new Response(400, [], 'oops'),
        ]);

        $validator = new ConstraintRssValidator($client);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }
}
