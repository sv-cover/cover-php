<?php
namespace App\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class CommitteeChoiceLoader implements ChoiceLoaderInterface
{
    private $showAll;
    private $showOwn;

    public function __construct(bool $showAll = false, bool $showOwn = true)
    {
        $this->showAll = $showAll;
        $this->showOwn = $showOwn;
    }

    public function loadChoiceList(callable $value = null): ChoiceListInterface
    {
        $choices = \get_model('DataModelCommissie')->get_committee_choices($this->showOwn);

        $factory = new DefaultChoiceListFactory();
        return $factory->createListFromChoices($choices, $value, [$this, 'filterCommittees']);
    }

    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        // Adapted from Symfony's AbstractChoiceLoader

        if (!$values) {
            return [];
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        // Adapted from Symfony's AbstractChoiceLoader

        if (!$choices) {
            return [];
        }

        if ($value) {
            // if a value callback exists, use it
            return array_map(fn ($item) => (string) $value($item), $choices);
        }

        return $this->loadChoiceList()->getValuesForChoices($choices);
    }

    public function filterCommittees($value) {
        if (
            \get_identity()->member_in_committee(COMMISSIE_BESTUUR)
            || \get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
            || \get_identity()->member_in_committee(COMMISSIE_EASY)
        )
            return true;

        return $this->showAll || \get_identity()->member_in_committee($value);
    }
}
