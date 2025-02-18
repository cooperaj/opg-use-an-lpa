<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \App\Service\Lpa\LpaAlreadyAdded
 */
class LpaAlreadyAddedTest extends TestCase
{
    use ProphecyTrait;

    private LpaService|ObjectProphecy $lpaServiceProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapProphecy;
    private FeatureEnabled|ObjectProphecy $featureEnabledProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;

    private string $userId;
    private string $lpaUid;
    private string $userLpaActorToken;

    public function setUp(): void
    {
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->userId = '12345';
        $this->lpaUid = '700000000543';
        $this->userLpaActorToken = 'abc123-456rtp';
    }

    private function getLpaAlreadyAddedService(): LpaAlreadyAdded
    {
        return new LpaAlreadyAdded(
            $this->lpaServiceProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::preSaveOfRequestFeature
     */
    public function returns_null_if_lpa_not_already_added_pre_feature(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $this->lpaServiceProphecy
            ->getAllLpasAndRequestsForUser('12345')
            ->willReturn(
                [
                    $this->userLpaActorToken => [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'lpa' => [
                            'uId' => $this->lpaUid
                        ],
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_null_if_lpa_not_already_added(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_not_activated_flag_if_lpa_requested_but_not_active(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id' => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                        'ActivateBy' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                    ],
                ]
            );

        $this->lpaServiceProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa' => [
                        'uId' => $this->lpaUid,
                        'caseSubtype' => 'hw',
                        'donor' => [
                            'uId' => '700000000444',
                            'firstname'     => 'Another',
                            'middlenames'   => '',
                            'surname'       => 'Person',
                        ],
                        'activationKeyDueDate' => null
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor' => [
                    'uId' => '700000000444',
                    'firstname' => 'Another',
                    'middlenames' => '',
                    'surname' => 'Person',
                ],
                'caseSubtype' => 'hw',
                'lpaActorToken' => $this->userLpaActorToken,
                'notActivated' => true,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_null_if_lpa_added_but_not_usable_found_in_api(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id' => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaServiceProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::preSaveOfRequestFeature
     */
    public function returns_lpa_data_if_lpa_is_already_added_pre_feature(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $this->lpaServiceProphecy
            ->getAllLpasAndRequestsForUser($this->userId)
            ->willReturn(
                [
                    'xyz321-987ltc' => [
                        'user-lpa-actor-token' => 'xyz321-987ltc',
                        'lpa' => [
                            'uId' => '700000000111',
                            'caseSubtype' => 'pfa',
                            'donor' => [
                                'uId' => '700000000222',
                                'firstname'     => 'Some',
                                'middlenames'   => '',
                                'surname'       => 'Person'
                            ],
                        ],
                    ],
                    $this->userLpaActorToken => [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'lpa' => [
                            'uId' => $this->lpaUid,
                            'caseSubtype' => 'hw',
                            'donor' => [
                                'uId' => '700000000444',
                                'firstname'     => 'Another',
                                'middlenames'   => '',
                                'surname'       => 'Person',
                            ],
                        ],
                    ]
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals([
            'donor'         => [
                'uId'           => '700000000444',
                'firstname'     => 'Another',
                'middlenames'   => '',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => $this->userLpaActorToken
        ], $lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_lpa_data_if_lpa_is_already_added(): void
    {
        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id' => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaServiceProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa' => [
                        'uId' => $this->lpaUid,
                        'caseSubtype' => 'hw',
                        'donor' => [
                            'uId' => '700000000444',
                            'firstname'     => 'Another',
                            'middlenames'   => '',
                            'surname'       => 'Person',
                        ],
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor' => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'           => 'hw',
                'lpaActorToken'         => $this->userLpaActorToken,
                'activationKeyDueDate'  => null
            ],
            $lpaAddedData
        );
    }
}
