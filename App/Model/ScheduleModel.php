<?php

namespace App\Model;

use JsonSerializable;

class ScheduleModel implements JsonSerializable
{
    private string $groupName = '';
    private array $groupCoordinates = [];
    private int $id = -1;

    private array $schedule = [];

    public function setID(int $id): void
    {
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->groupName = $name;
    }

    public function getName(): string
    {
        return $this->groupName;
    }

    public function setGroupCoordinates(array $groupCoordinates): void
    {
        $this->groupCoordinates = $groupCoordinates;
    }

    public function getGroupCoordinates(): array
    {
        return $this->groupCoordinates;
    }

    public function setSchedule(array $schedule): void
    {
        $this->schedule = $schedule;
    }

    public function getSchedule(): array
    {
        return $this->schedule;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getID(),
            'name' => $this->getName(),
            'coordinates' => $this->getGroupCoordinates(),
            'schedule' => $this->getSchedule()
        ];
    }
}