<?php

namespace App\Console\Commands;

use App\Data\EventDataTransformer;
use App\Http\Clients\UpstateClient;
use App\Models\Event;
use Glhd\ConveyorBelt\IteratesEnumerable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Enumerable;

class PullEventsCommand extends Command
{
    use IteratesEnumerable;

    protected $signature = 'pull:events';

    protected $description = 'Download events using a cron or console command and cache them in the database';

    /**
     * @throws RequestException
     */
    public function collect(): Enumerable
    {
        $client = new UpstateClient;

        return $client
            ->getEvents()
            ->map(fn ($event_from_api) => EventDataTransformer::from($event_from_api));
    }

    public function handleRow(EventDataTransformer $row): void
    {
        $this->progressMessage('Importing...');

        $this->progressSubMessage($row->event_name);

        Event::updateOrCreate($row->uniqueIdentifier(), [
            'event_uuid' => $row->uuid,
            'event_name' => $row->event_name,
            'group_name' => $row->group_name,
            'description' => $row->description,
            'rsvp_count' => $row->rsvp_count,
            'active_at' => $row->time,
            'cancelled_at' => $row->getCancelledAtOrNull(),
            'uri' => $row->url,
            'venue_id' => $row->hasVenue()
                ? $row->resolveVenue()->id
                : null,
            'cache' => [],
        ]);
    }

    /**
     * @throws RequestException
     */
    public function afterLastRow(): void
    {
        // Clean up all events that no longer exist on the API.
        $event_uuids = $this->collect()->pluck('uuid');

        Event::query()
            ->whereNotIn('event_uuid', $event_uuids)
            ->where('active_at', '>', now())
            ->update([
                'cancelled_at' => now(),
            ]);
    }
}
