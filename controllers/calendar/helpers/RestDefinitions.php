<?php

namespace humhub\modules\smartVillage\controllers\calendar\helpers;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\rest\definitions\UserDefinitions;

class RestDefinitions
{
    public static function getCalendarEntry(CalendarEntry $entry)
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'description' => $entry->description,
            'start_datetime' => $entry->start_datetime,
            'end_datetime' => $entry->end_datetime,
            'all_day' => (int)$entry->all_day,
            'participation_mode' => (int)$entry->participation_mode,
            'color' => $entry->color,
            'allow_decline' => (int)$entry->allow_decline,
            'allow_maybe' => (int)$entry->allow_maybe,
            'time_zone' => $entry->time_zone,
            'location' => $entry->location,
            'participant_info' => $entry->participant_info,
            'closed' => (int)$entry->closed,
            'max_participants' => (int)$entry->max_participants,
            'content' => ContentDefinitions::getContent($entry->content),
            'participants' => static::getParticipantUsers($entry->getParticipantEntries()->with('user')->all())
        ];
    }

    private static function getParticipantUsers($participants)
    {
        $result = [
            'attending' => [],
            'maybe' => [],
            'declined' => []
        ];

        foreach ($participants as $participant) {
            if ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) {
                $result['attending'][] = UserDefinitions::getUserShort($participant->user);
            } elseif ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE){
                $result['maybe'][] = UserDefinitions::getUserShort($participant->user);
            } elseif ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED){
                $result['declined'][] = UserDefinitions::getUserShort($participant->user);
            }
        }

        return $result;
    }
}