<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\AccountBundle\Security\AccountUserProvider;
use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridAccountUserNameListener;

class CheckoutGridAccountUserNameListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutGridAccountUserNameListener
     */
    protected $testable;

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;


    public function testOnBuildBefore()
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\AccountBundle\Security\AccountUserProvider')
                               ->disableOriginalConstructor()
                               ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
                      ->disableOriginalConstructor()
                      ->getMock();

        $configObject = $this->getMockBuilder('Oro\Component\Config\Common\ConfigObject')
                             ->disableOriginalConstructor()
                             ->getMock();

        $exampleColumns = [
            'accountUserName' => 'Dummy',
            'createdAt'       => '2016-01-01'
        ];

        $configObject->expects($this->at(0))
                     ->method('offsetGetByPath')
                     ->with('[columns]')
                     ->willReturn($exampleColumns);

        $configObject->expects($this->at(1))
                     ->method('offsetUnsetByPath')
                     ->with('[columns][accountUserName]');

        $configObject->expects($this->at(2))
                     ->method('offsetUnsetByPath')
                     ->with('[sorters][columns][accountUserName]');

        $event->expects($this->once())
              ->method('getConfig')
              ->willReturn($configObject);

        $this->provider->expects($this->once())
                       ->method('isGrantedViewLocal')
                       ->willReturn(false);

        $this->testable = new CheckoutGridAccountUserNameListener($this->provider);
        $this->testable->onBuildBefore($event);
    }
}
