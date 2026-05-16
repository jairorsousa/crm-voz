<?php

namespace App\Support\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use App\Enums\CompanySize;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\ContactType;
use App\Enums\LeadTemperature;
use App\Enums\OpportunityStatus;
use App\Enums\PriorityLevel;

class CrmOptions
{
    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function all(): array
    {
        return [
            'companyStatuses' => self::fromEnum(CompanyStatus::cases()),
            'leadTemperatures' => self::fromEnum(LeadTemperature::cases()),
            'priorities' => self::fromEnum(PriorityLevel::cases()),
            'companySizes' => self::fromEnum(CompanySize::cases()),
            'companyTypes' => self::fromEnum(CompanyType::cases()),
            'contactTypes' => self::fromEnum(ContactType::cases()),
            'opportunityStatuses' => self::fromEnum(OpportunityStatus::cases()),
            'activityTypes' => self::fromEnum(ActivityType::cases()),
            'activityStatuses' => self::fromEnum(ActivityStatus::cases()),
            'communicationChannels' => self::fromEnum(CommunicationChannel::cases()),
            'communicationDirections' => self::fromEnum(CommunicationDirection::cases()),
            'communicationOrigins' => self::fromEnum(CommunicationOrigin::cases()),
            'communicationStatuses' => self::fromEnum(CommunicationStatus::cases()),
        ];
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private static function fromEnum(array $cases): array
    {
        return array_map(fn (object $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $cases);
    }
}
