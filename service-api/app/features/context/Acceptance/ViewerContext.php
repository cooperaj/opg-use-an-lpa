<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Acceptance
 *
 * @property string viewerCode
 * @property string donorSurname
 * @property array lpa
 * @property string viewerCodeOrganisation
 * @property string lpaViewedBy
 */
class ViewerContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    /**
     * @Given I have been given access to an LPA via share code
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaShareCode(): void
    {
        $this->viewerCode = '1111-1111-1111';
        $this->donorSurname = 'Deputy';
        $this->viewerCodeOrganisation = 'santander';
        $this->lpaViewedBy = 'Santander';

        $this->lpa = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'),
            true
        );
    }

    /**
     * @Given I have been given access to a cancelled LPA via share code
     */
    public function iHaveBeenGivenAccessToUseACancelledLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaShareCode();
        $this->lpa['status'] = 'Cancelled';
    }

    /**
     * @Given I access the viewer service
     */
    public function iAccessTheViewerService(): void
    {
        // Not used in this context
    }

    /**
     * @When I give a share code that's been cancelled
     */
    public function iGiveAShareCodeThatHasBeenCancelled(): void
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');
        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa['uId'],
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry,
                            'Cancelled'  => (new \DateTime('now'))->format('c'),
                        ]
                    )
                ]
            )
        );

        // Lpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/viewer-codes/summary',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname
            ]
        );
    }

    /**
     * @Then I can see a message the LPA has been cancelled
     */
    public function iCanSeeAMessageTheLPAHasBeenCancelled(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_GONE);
        $lpaData = $this->getResponseAsJson();
        Assert::assertEquals($lpaData['title'], 'Gone');
        Assert::assertEquals($lpaData['details'], 'Share code cancelled');
    }

    /**
     * @When I give a valid LPA share code
     */
    public function iGiveAValidLPAShareCode(): void
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode'   => $this->viewerCode,
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => (new \DateTime('now'))->format('c'),
                            'Expires'      => $lpaExpiry,
                            'Organisation' => $this->viewerCodeOrganisation,
                        ]
                    )
                ]
            )
        );

        // Lpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/viewer-codes/summary',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname
            ]
        );
    }

    /**
     * @When /^I enter an organisation name and confirm the LPA is correct$/
     */
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);

        $lpaExpiry = (new \DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode'   => $this->viewerCode,
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => (new \DateTime('now'))->format('c'),
                            'Expires'      => $lpaExpiry,
                            'Organisation' => $this->viewerCodeOrganisation,
                        ]
                    )
                ]
            )
        );

        // Lpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // ViewerCodeActivity::recordSuccessfulLookupActivity
        $this->awsFixtures->append(new Result([]));

        $this->apiPost(
            '/v1/viewer-codes/full',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname,
                'organisation' => $this->lpaViewedBy
            ]
        );
    }

    /**
     * @Then I can see the full details of the valid LPA
     */
    public function iCanSeeTheFullDetailsOfTheValidLPA(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);
    }

    /**
     * @Then /^I see a message that LPA has been cancelled$/
     */
    public function iSeeAMessageThatLPAHasBeenCancelled(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);
        Assert::assertEquals('Cancelled', $lpaData['lpa']['status']);
    }

    /**
     * @When /^I realise the LPA is incorrect$/
     */
    public function iRealiseTheLPAIsCorrect(): void
    {
        // Not used in this context
    }

    /**
     * @Then /^I want to see an option to re-enter code$/
     */
    public function iWantToSeeAnOptionToReEnterCode(): void
    {
        // Not used in this context
    }

    /**
     * @Then /^I want to see an option to check another LPA$/
     */
    public function iWantToSeeAnOptionToCheckAnotherLPA(): void
    {
        // Not used in this context
    }
}
