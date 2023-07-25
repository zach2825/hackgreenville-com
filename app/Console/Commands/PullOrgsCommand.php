<?php

namespace App\Console\Commands;

use App\Data\OrganizationData;
use App\Http\Clients\UpstateClient;
use App\Models\Org;
use Glhd\ConveyorBelt\IteratesEnumerable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Enumerable;

class PullOrgsCommand extends Command
{
    use IteratesEnumerable;

    // TODO :: account for this switch org-cleanup
    protected $signature = 'pull:orgs {--org-cleanup : clean out duplicate deleted orgs}';

    protected $description = 'Download and cache organizations in the database.';

    /**
     * @throws RequestException
     */
    public function collect(): Enumerable
    {
        return (new UpstateClient)
            ->getOrgs()
            ->transform(fn ($org_from_api) => OrganizationData::from($org_from_api));
    }

    public function handleRow(OrganizationData $row): void
    {
        $this->progressMessage('Importing Organizations');
        $this->progressSubMessage($row->title);

        Org::updateOrCreate([
            'title' => $row->title,
            'city' => $row->field_city,
        ], [
            'title' => $row->title,
            'city' => $row->field_city,
            'category_id' => $row->resolveCategory()->id,
            'path' => $row->path,
            'focus_area' => $row->field_focus_area,
            'uri' => $row->field_homepage,
            'primary_contact_person' => $row->field_primary_contact_person,
            'organization_type' => $row->field_organization_type,
            'event_calendar_uri' => $row->field_event_calendar_homepage,
            'cache' => '',
        ]);
    }
}
