<?php

namespace App\Controller;

class ApiController
{
    private string $action = '';
    private ScheduleController $schedule;

    private mixed $result;

    public function __construct(string $action)
    {
        $this->action = $action;

        $schedule = new ScheduleController(APP . '/files/schedule.xlsx');
        $schedule->setReaderCoordinates(4, 47, range('B', 'R'));
        $schedule->load();

        $schedule->fill();

        $this->schedule = $schedule;

    }

    public function runAction($action, $params): bool|object
    {
        if (is_callable([ApiController::class, $action])) {
            try {
                $this->result = $this->{$action}($params);
            } catch (\RuntimeException $e) {
                return false;
            }
        } else {
            return false;
        }

        return $this;

    }

    protected function reloadFile(string $url)
    {

    }

    protected function getAllowedGroups($params)
    {
        return $this->schedule->getAllGroups();
    }

    protected function getSchedule($params)
    {
        $params = explode('/', $params);

        return $this->schedule->getScheduleByGroupIDAndDate($params[0], $params[1]);
    }

    public function asJson()
    {
        return json_encode(['result' => $this->result], flags: JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
    }
}