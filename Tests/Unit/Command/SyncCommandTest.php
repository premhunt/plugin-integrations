<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\IntegrationsBundle\Command\SyncCommand;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * This test must run in a separate process because it sets the global constant
 * MAUTIC_INTEGRATION_SYNC_IN_PROGRESS which breaks other tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SyncCommandTest extends \PHPUnit_Framework_TestCase
{
    private const INTEGRATION_NAME = 'Test';

    /**
     * @var SyncServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncService;

    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paramsHelper;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->syncService  = $this->createMock(SyncServiceInterface::class);
        $this->paramsHelper = $this->createMock(CoreParametersHelper::class);
        $application        = new Application();

        $application->add(new SyncCommand($this->syncService, $this->paramsHelper));

        // env is global option. Must be defined.
        $application->getDefinition()->addOption(
            new InputOption(
                '--env',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'The environment to operate in.',
                'DEV'
            )
        );

        $this->commandTester = new CommandTester(
            $application->find(SyncCommand::NAME)
        );
    }

    public function testExecuteWithoutIntetrationName(): void
    {
        $this->assertSame(1, $this->commandTester->execute([]));
    }

    public function testExecuteWithSomeOptions(): void
    {
        $this->syncService->expects($this->once())
            ->method('processIntegrationSync')
            ->with($this->callback(function (InputOptionsDAO $inputOptionsDAO) {
                $this->assertSame(self::INTEGRATION_NAME, $inputOptionsDAO->getIntegration());
                $this->assertSame(['123', '345'], $inputOptionsDAO->getMauticObjectIds()->getObjectIdsFor(MauticSyncDataExchange::OBJECT_CONTACT));
                $this->assertNull($inputOptionsDAO->getIntegrationObjectIds());
                $this->assertTrue($inputOptionsDAO->pullIsEnabled());
                $this->assertFalse($inputOptionsDAO->pushIsEnabled());

                return true;
            }));

        $code = $this->commandTester->execute([
            'integration'        => self::INTEGRATION_NAME,
            '--disable-push'     => true,
            '--mautic-object-id' => ['contact:123', 'contact:345'],
        ]);

        $this->assertSame(0, $code);
    }

    public function testExecuteWhenSyncThrowsException(): void
    {
        $this->syncService->expects($this->once())
            ->method('processIntegrationSync')
            ->with($this->callback(function (InputOptionsDAO $inputOptionsDAO) {
                $this->assertSame(self::INTEGRATION_NAME, $inputOptionsDAO->getIntegration());

                return true;
            }))
            ->will($this->throwException(new \Exception()));

        $code = $this->commandTester->execute(['integration' => self::INTEGRATION_NAME]);

        $this->assertSame(1, $code);
    }
}
