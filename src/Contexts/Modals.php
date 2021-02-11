<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\Apps\Coerce;
use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Exception;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;
use Throwable;
use RuntimeException;

class Modals
{
    /** @var Context */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $triggerId
     * @return array
     */
    public function open($modal, ?string $triggerId = null): array
    {
        try {
            $triggerId = $triggerId ?? (string) $this->context->payload()->get('trigger_id', true);
            $result = $this->context->api()->viewsOpen([
                'trigger_id' => $triggerId,
                'view' => Coerce::modal($modal)->toJson(),
            ]);

            if (!$result->getOk()) {
                throw new RuntimeException('Result is not OK');
            }

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.open` failed', 0, $ex);
        }
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $triggerId
     * @return array
     */
    public function push($modal, ?string $triggerId = null): array
    {
        try {
            $triggerId = $triggerId ?? (string) $this->context->payload()->get('trigger_id', true);
            $result = $this->context->api()->viewsPush([
                'trigger_id' => $triggerId,
                'view' => Coerce::modal($modal)->toJson(),
            ]);

            if (!$result->getOk()) {
                throw new RuntimeException('Result is not OK');
            }

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.push` failed', 0, $ex);
        }
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $viewId
     * @param string|null $hash
     * @param string|null $externalId
     * @return array
     */
    public function update($modal, ?string $viewId = null, ?string $hash = null, ?string $externalId = null): array
    {
        $payload = $this->context->payload();

        try {
            if ($externalId !== null) {
                $viewId = null;
            } else {
                $viewId = $viewId ?? (string) $payload->get('view.id', true);
            }

            $result = $this->context->api()->viewsUpdate(array_filter([
                'view_id' => $viewId,
                'external_id' => $externalId,
                'view' => Coerce::modal($modal)->toJson(),
                'hash' => $payload->get('view.hash'),
            ]));

            if (!$result->getOk()) {
                throw new RuntimeException('Result is not OK');
            }

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.update` failed', 0, $ex);
        }
    }
}
