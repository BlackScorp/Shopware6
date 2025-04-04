<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Subscriber;

use Kiener\MolliePayments\Helper\ProfileHelper;
use Kiener\MolliePayments\Service\SettingsService;
use Kiener\MolliePayments\Setting\MollieSettingStruct;
use Kiener\MolliePayments\Struct\CustomerStruct;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Profile;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    /** @var SettingsService */
    private $settingsService;

    /** @var LoggerInterface */
    private $logger;

    /** @var MollieApiClient */
    private $apiClient;

    /** @var array<mixed> */
    private $profileIdStorage = [];

    public function __construct(SettingsService $settingsService, LoggerInterface $logger)
    {
        $this->settingsService = $settingsService;
        $this->logger = $logger;

        $this->apiClient = new MollieApiClient();
    }

    public static function getSubscribedEvents()
    {
        return [
            'system_config.written' => 'onSystemConfigWritten',
        ];
    }

    public function onSystemConfigWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getPayloads() as $payload) {
            if (! isset($payload['configurationKey'])) {
                continue;
            }

            $this->checkSystemConfigChange(
                (string) $payload['configurationKey'],
                $payload['configurationValue'],
                $payload['salesChannelId'] ?? null,
                $event->getContext()
            );
        }
    }

    /**
     * @param mixed $value
     */
    private function checkSystemConfigChange(string $key, $value, ?string $salesChannelId, Context $context): void
    {
        if (in_array($key, [
            SettingsService::SYSTEM_CONFIG_DOMAIN . SettingsService::LIVE_PROFILE_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . SettingsService::TEST_PROFILE_ID,
        ])) {
            $this->fixProfileIdAfterChange(
                $key,
                $value,
                $salesChannelId,
                strpos($key, SettingsService::TEST_PROFILE_ID) !== false,
                $context
            );
        }

        if (in_array($key, [
            SettingsService::SYSTEM_CONFIG_DOMAIN . SettingsService::LIVE_API_KEY,
            SettingsService::SYSTEM_CONFIG_DOMAIN . SettingsService::TEST_API_KEY,
        ])) {
            $this->fetchProfileIdForApiKey(
                $value,
                $salesChannelId,
                strpos($key, SettingsService::TEST_API_KEY) !== false,
                $context
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    private function fetchProfileIdForApiKey($value, ?string $salesChannelId, bool $testMode, Context $context): void
    {
        $profileKey = SettingsService::SYSTEM_CONFIG_DOMAIN .
            ($testMode ?
                SettingsService::TEST_PROFILE_ID :
                SettingsService::LIVE_PROFILE_ID);

        if (empty($value)) {
            // If this api key has been "deleted", also remove the profile ID.

            $this->logger->debug(
                'API key has been removed, removing associated profile ID',
                [
                    'salesChannelId' => $salesChannelId ?? 'null',
                    'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
                ]
            );

            $this->settingsService->setProfileId(null, $salesChannelId, $testMode);

            return;
        }

        $this->logger->debug(
            'Fetching profile ID',
            [
                'salesChannelId' => $salesChannelId ?? 'null',
                'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
            ]
        );

        $this->apiClient->setApiKey($value);

        $profile = ProfileHelper::getProfile($this->apiClient, new MollieSettingStruct());

        if (! $profile instanceof Profile) {
            $this->logger->error(
                'Could not get profile using these settings',
                [
                    'salesChannelId' => $salesChannelId ?? 'null',
                    'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
                ]
            );

            return;
        }

        $this->profileIdStorage[$salesChannelId . $profileKey] = $profile->id;

        $this->logger->debug(
            'Saving profile ID',
            [
                'salesChannelId' => $salesChannelId ?? 'null',
                'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
                'profileId' => $profile->id,
            ]
        );

        $this->settingsService->setProfileId($profile->id, $salesChannelId, $testMode);
    }

    /**
     * Why do we need to fix the profile ID?
     * When adding a key to the system config programmatically, even if there is no field for it in config.xml,
     * when saving the configuration in the administration, Shopware will also save those keys.
     * We need to fix the profile ID, because we fetch the new profile ID from Mollie and save it to the system config,
     * and then Shopware overwrites it with the old one afterwards.
     *
     * @param mixed $value
     */
    private function fixProfileIdAfterChange(string $key, $value, ?string $salesChannelId, bool $testMode, Context $context): void
    {
        if (isset($this->profileIdStorage[$salesChannelId . $key])) {
            // If the old $value is the same as the new profile ID in storage, then dont set it again
            // Will end up in an endless loop otherwise.
            if ($this->profileIdStorage[$salesChannelId . $key] === $value) {
                return;
            }

            $this->logger->debug(
                'A new profile ID was fetched, but the admin saved the old one again, correcting mistake.',
                [
                    'salesChannelId' => $salesChannelId ?? 'null',
                    'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
                    'profileId' => $value,
                ]
            );

            $this->settingsService->setProfileId($this->profileIdStorage[$salesChannelId . $key], $salesChannelId, $testMode);
        } elseif ($value) {
            $this->logger->debug(
                'Removing profile ID',
                [
                    'salesChannelId' => $salesChannelId ?? 'null',
                    'mode' => $testMode ? CustomerStruct::TEST_MODE : CustomerStruct::LIVE_MODE,
                ]
            );

            $this->settingsService->setProfileId(null, $salesChannelId, $testMode);
        }
    }
}
