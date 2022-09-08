<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FilterOperatorProvider implements FilterOperatorProviderInterface
{
    private EventDispatcherInterface $dispatcher;

    private \Symfony\Contracts\Translation\TranslatorInterface $translator;

    /**
     * @var mixed[]
     */
    private array $cachedOperators = [];

    public function __construct(
        EventDispatcherInterface $dispatcher,
        \Symfony\Contracts\Translation\TranslatorInterface $translator
    ) {
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    /**
     * @return mixed[]
     */
    public function getAllOperators(): array
    {
        if (empty($this->cachedOperators)) {
            $event = new LeadListFiltersOperatorsEvent([], $this->translator);

            $this->dispatcher->dispatch($event, LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE);

            $this->cachedOperators = $this->translateOperatorLabels($event->getOperators());
        }

        return $this->cachedOperators;
    }

    /**
     * @param mixed[] $operators
     *
     * @return mixed[]
     */
    private function translateOperatorLabels(array $operators): array
    {
        foreach ($operators as $key => $operatorSettings) {
            $operators[$key]['label'] = $this->translator->trans($operatorSettings['label']);
        }

        return $operators;
    }
}
