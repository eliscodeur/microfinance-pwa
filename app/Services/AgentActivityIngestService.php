<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentActivityLog;
use App\Support\AuditValueSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class AgentActivityIngestService
{
    public const EVENTS = [
        'agent_session_opened',
        'agent_session_denied',
        'agent_session_closed',
        'agent_pin_changed',
        'agent_collecte_recorded',
        'agent_cycle_closed',
        'agent_sync_submitted',
    ];

    public function __construct(private AuditValueSanitizer $sanitizer)
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, string>
     */
    public function ingest(Agent $agent, array $events, Request $request, ?string $syncUuid = null): array
    {
        $accepted = [];

        foreach ($events as $event) {
            $uuid = $this->ingestOne($agent, $event, $request, $syncUuid);
            if ($uuid) {
                $accepted[] = $uuid;
            }
        }

        return $accepted;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function ingestOne(Agent $agent, array $event, Request $request, ?string $syncUuid = null): ?string
    {
        $uuid = (string) ($event['uuid'] ?? '');
        $name = (string) ($event['event'] ?? '');

        if ($uuid === '' || ! in_array($name, self::EVENTS, true)) {
            return null;
        }

        if (AgentActivityLog::query()->where('event_uuid', $uuid)->exists()) {
            return $uuid;
        }

        $occurredAt = $this->parseOccurredAt($event['occurred_at'] ?? null);
        $receivedAt = now();
        $payload    = $this->sanitizer->sanitize((array) ($event['payload'] ?? []));
        $connectivity = in_array($event['connectivity'] ?? null, ['online', 'offline'], true)
            ? $event['connectivity']
            : null;

        $user = $agent->user;
        $deviceId = isset($event['device_id']) ? mb_substr((string) $event['device_id'], 0, 64) : null;
        $entityType = isset($event['entity_type']) ? mb_substr((string) $event['entity_type'], 0, 64) : null;
        $entityUid = isset($event['entity_uid']) ? mb_substr((string) $event['entity_uid'], 0, 191) : null;

        DB::transaction(function () use (
            $agent,
            $user,
            $uuid,
            $name,
            $connectivity,
            $entityType,
            $entityUid,
            $deviceId,
            $syncUuid,
            $payload,
            $occurredAt,
            $receivedAt,
            $request
        ) {
            AgentActivityLog::create([
                'event_uuid'   => $uuid,
                'agent_id'     => $agent->id,
                'user_id'      => $user?->id,
                'event'        => $name,
                'connectivity' => $connectivity,
                'entity_type'  => $entityType,
                'entity_uid'   => $entityUid,
                'device_id'    => $deviceId,
                'sync_uuid'    => $syncUuid,
                'payload'      => $payload,
                'occurred_at'  => $occurredAt,
                'received_at'  => $receivedAt,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent()
                    ? mb_substr($request->userAgent(), 0, 1023)
                    : null,
            ]);

            Audit::create([
                'user_type'      => $user ? $user->getMorphClass() : null,
                'user_id'        => $user?->getAuthIdentifier(),
                'event'          => $name,
                'auditable_type' => Agent::class,
                'auditable_id'   => $agent->getKey(),
                'old_values'     => [],
                'new_values'     => array_filter([
                    'channel'         => 'pwa_field',
                    'connectivity'    => $connectivity,
                    'occurred_at'     => $occurredAt->toIso8601String(),
                    'received_at'     => $receivedAt->toIso8601String(),
                    'device_id'       => $deviceId,
                    'entity_type'     => $entityType,
                    'entity_uid'      => $entityUid,
                    'sync_uuid'       => $syncUuid,
                    'event_uuid'      => $uuid,
                    'code_agent'      => $agent->code_agent,
                    'payload'         => $payload,
                ], fn ($value) => $value !== null && $value !== ''),
                'url'            => $request->fullUrl(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent()
                    ? mb_substr($request->userAgent(), 0, 1023)
                    : null,
                'tags'           => implode(',', array_filter([
                    'agent',
                    'field',
                    $connectivity,
                ])),
                'created_at'     => $occurredAt,
                'updated_at'     => $receivedAt,
            ]);
        });

        return $uuid;
    }

    private function parseOccurredAt(mixed $value): Carbon
    {
        try {
            $parsed = Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }

        if ($parsed->isFuture() && $parsed->diffInMinutes(now()) > 15) {
            return now();
        }

        return $parsed;
    }
}
