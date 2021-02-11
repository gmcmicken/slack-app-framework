<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use JsonSerializable;

class Payload implements JsonSerializable
{
    use HasData;

    /** @var PayloadType */
    private $type;

    public function __construct(array $data = [])
    {
        if (isset($data['type'])) {
            $this->type = PayloadType::withValue($data['type']);
        } elseif (isset($data['command'])) {
            $this->type = PayloadType::command();
        } else {
            $this->type = PayloadType::unknown();
        }

        $this->setData($data);
    }

    public function getType(): PayloadType
    {
        return $this->type;
    }

    public function isType(PayloadType $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Returns the main ID/name/type for the payload type used for route indexing.
     *
     * @return string|null
     */
    public function getTypeId(): ?string
    {
        $field = $this->type->idField();
        $id = $field ? $this->get($field) : null;
        if ($id !== null) {
            $id = ltrim($id, '/');
        }

        return $id;
    }

    /**
     * Returns the api_api_id property of the payload, common to almost all payload types.
     *
     * @return string|null
     */
    public function getAppId(): ?string
    {
        return $this->get('api_app_id');
    }

    /**
     * Get the enterprise ID for the payload.
     *
     * @return string|null
     */
    public function getEnterpriseId(): ?string
    {
        return $this->getOneOf([
            'authorizations.0.enterprise_id',
            'enterprise.id',
            'enterprise_id',
            'team.enterprise_id',
            'event.enterprise',
            'event.enterprise_id',
        ]);
    }

    /**
     * Get the team/workspace ID for the payload.
     *
     * @return string|null
     */
    public function getTeamId(): ?string
    {
        return $this->getOneOf(['authorizations.0.team_id', 'team.id', 'team_id', 'event.team', 'user.team_id']);
    }

    /**
     * Get the channel ID for the payload.
     *
     * @return string|null
     */
    public function getChannelId(): ?string
    {
        return $this->getOneOf(['channel.id', 'channel_id', 'event.channel', 'event.item.channel']);
    }

    /**
     * Get the user ID for the payload.
     *
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->getOneOf(['user.id', 'user_id', 'event.user']);
    }

    /**
     * Check if the payload is from and enterprise installation.
     *
     * @return bool
     */
    public function isEnterpriseInstall(): bool
    {
        $value = $this->getOneOf(['authorizations.0.is_enterprise_install', 'is_enterprise_install']);

        return $value === true || $value === 'true';
    }

    /**
     * Get the submitted state from the payload, if present.
     *
     * Can be present for view_submission, view_closed, and some block_action requets.
     *
     * @return DataBag
     */
    public function getState(): DataBag
    {
        return new DataBag($this->getOneOf(['view.state.values', 'state.values']) ?? []);
    }
}
