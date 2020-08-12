<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware\Configurator;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;

class SetRepository implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;
    /**
     * @var Set $set
     */
    private $set;

     /**
     * @var array $options
     */
    private $options;

    /**
     * @var array $groups
     */
    private $groups;

    protected function createSet(string $name) {
        $set = new Set();
        $this->modelManager->persist($set);
        $set->setName($name);
        $set->setPublic(false);
        $set->setType(1);
        return $set;
    }

    public function getSet(string $name) {
        $set = $this->modelManager->getRepository(Set::class)->findOneBy(['name' => $name]);
        if ($set !== null) {
            $this->modelManager->remove($set);
            $this->modelManager->flush();
        }

        $this->set = $this->createSet($name);
        $this->groups = [];
        $this->options = [];

        return $this->set;
    }

    public function addOption(Option $option) {
        $group = $option->getGroup();
        $groupName = $group->getName();
        $optionName = $option->getName();
        $setName = $this->set->getName();

        if (! isset($this->groups[$groupName])) {
            $this->log->debug(sprintf('%s: Adding group %s to set %s.',
                __FUNCTION__,
                $groupName,
                $setName
            ));
            $this->groups[$groupName] = $group;
            $this->set->getGroups()->add($group);

        }

        if (! isset($this->options[$optionName])) {
            $this->log->debug(sprintf('%s: Adding option %s to set %s.',
                __FUNCTION__,
                $optionName,
                $setName
            ));
            $this->options[$optionName] = $option;
            $this->set->getOptions()->add($option);

        }
    }
}