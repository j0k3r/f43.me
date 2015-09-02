<?php

namespace Api43\FeedBundle\Tests\Validator;

use Api43\FeedBundle\Validator\Constraints\ConstraintRssValidator;
use Api43\FeedBundle\Validator\Constraints\ConstraintRss;
use GuzzleHttp\Exception\RequestException;

class ConstraintRssValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatorValid()
    {
        $constraint = new ConstraintRss();

        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('This is a valid'));

        $validator = new ConstraintRssValidator($guzzle);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFail()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo($constraint->message),
                $this->equalTo(array('%string%' => 'http://0.0.0.0'))
            );

        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('This is a not valid'));

        $validator = new ConstraintRssValidator($guzzle);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailFirst()
    {
        $constraint = new ConstraintRss();

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo($constraint->message),
                $this->equalTo(array('%string%' => 'http://0.0.0.0'))
            );

        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new RequestException('oops', $request)),
                'This is a not valid'
            ));

        $validator = new ConstraintRssValidator($guzzle);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }

    public function testValidatorFailTwice()
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $constraint = new ConstraintRss();

        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new RequestException('oops', $request)),
                $this->throwException(new RequestException('oops', $request))
            ));

        $validator = new ConstraintRssValidator($guzzle);
        $validator->initialize($context);
        $validator->validate('http://0.0.0.0', $constraint);
    }
}
