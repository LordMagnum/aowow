<?php

namespace Aowow;

if (!defined('AOWOW_REVISION'))
    die('illegal access');

if (!CLI)
    die('not in cli mode');


CLISetup::registerSetup("sql", new class extends SetupScript
{
    protected $info = array(
        'events' => [[], CLISetup::ARGV_PARAM, 'Compiles data for type: Event from world db.']
    );

    protected $worldDependency = ['game_event', 'game_event_prerequisite'];

    public function generate(array $ids = []) : bool
    {
        DB::Aowow()->query('TRUNCATE ?_events');

        $events = DB::World()->select(
           'SELECT    ge.eventEntry,
                      holiday,
                      0,                                    -- cuFlags
                      IFNULL(UNIX_TIMESTAMP(start_time), 0),
                      IFNULL(UNIX_TIMESTAMP(end_time), 0),
                      occurence * 60,
                      length * 60,
                      IF (gep.eventEntry IS NOT NULL, GROUP_CONCAT(prerequisite_event SEPARATOR " "), NULL),
                      description
            FROM      game_event ge
            LEFT JOIN game_event_prerequisite gep ON gep.eventEntry = ge.eventEntry
          { WHERE     ge.eventEntry IN (?a) }
            GROUP BY  ge.eventEntry',
            $ids ?: DBSIMPLE_SKIP
        );

        foreach ($events as $e)
            DB::Aowow()->query('INSERT INTO ?_events VALUES (?a)', array_values($e));

        $this->reapplyCCFlags('events', Type::WORLDEVENT);

        return true;
    }
});

?>
