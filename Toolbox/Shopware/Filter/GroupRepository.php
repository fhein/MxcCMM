<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcCommons\Toolbox\Shopware\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;

class GroupRepository implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;
    /**
     * @var array $data
     */
    protected $data;

    public function __construct() {
        $this->createLookupTable();
    }

    protected function createLookupTable()
    {
        $dql = sprintf('SELECT o.name oName, v.value vName FROM %s o JOIN %s v WHERE v.option = o.id',
            Option::class,
        Value::class
        );
        $array = $this->modelManager->createQuery($dql)->getScalarResult();
        $this->data = [];
        foreach ($array as $entry) {
            $this->data[$entry['oName']]['group'] = true;
            $this->data[$entry['oName']]['options'][$entry['vName']] = true;
        }
    }

    public function createGroup(string $name) {
        $group = $this->getGroup($name);
        if ($group instanceof Option) {
            $this->log->debug(sprintf('%s: Returning existing Shopware filter option %s.',
                __FUNCTION__,
                $name
            ));
            return $group;
        }

        $this->log->debug(sprintf('%s: Creating shopware filter option %s',
            __FUNCTION__,
            $name
        ));
        $group = new Option();
        $this->modelManager->persist($group);

        $group->setName($name);
        $group->setFilterable(true);
        $this->data[$name]['group'] = $group;
        $this->data[$name]['options'] = [];
        return $group;
    }

    public function getGroup(string $name) {
        $group = $this->data[$name]['group'] ?? null;
        if ($group  === true) {
            $group = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $name]);
            $this->data[$name]['group'] = $group;
            $this->modelManager->persist($group);
        }
        return $group;
    }

    public function createOption(string $groupName, string $optionName) : ?Value
    {
        // we do not create an option if we do not know the group
        $group = $this->getGroup($groupName);
        if (null === $group) return null;

        // if we already have the option return it
        $option = $this->getOption($groupName, $optionName);
        if ($option instanceof Value) {
            $this->log->debug(sprintf('%s: Returning existing option %s of property group %s.',
                __FUNCTION__,
                $optionName,
                $groupName
            ));
            return $option;
        }

        $this->log->debug(sprintf('%s: Creating option %s for property group %s.',
            __FUNCTION__,
            $optionName,
            $groupName
        ));

        // create new option
        $option = new Value($group, $optionName);
        $this->modelManager->persist($option);
        /**
         * @var ArrayCollection $options
         */
        $options = $group->getValues();
        $options->add($option);

        $option->setPosition(count($this->data[$groupName]['options']));
        $this->data[$groupName]['options'][$optionName] = $option;
        return $option;
    }

    public function getOption(string $groupName, string $optionName) : ?Option {
        $option = $this->data[$groupName]['options'][$optionName] ?? null;
        $groupId = $this->getGroup($groupName)->getId();
        if ($option === true) {
            $dql = sprintf("SELECT o FROM %s v JOIN %s o WHERE v.option = %s AND v.option = '%s'",
                Value::class,
                Option::class,
                 $groupId,
                 $optionName);
            $option = $this->modelManager->createQuery($dql)->getResult()[0];
            $this->data[$groupName]['options'][$optionName] = $option;
        }
        return $option;
    }

    public function deleteGroup(string $groupName) {
        // cascade remove does not work because the shopware doctrine config is incomplete
        $group = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $groupName]);
        if ($group) {
            // delete the options
            $dql = sprintf( "DELETE %s value WHERE value.option = %s",
                Value::class,
                $group->getId()
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();

            // delete the groups
            $dql = sprintf( "DELETE %s option WHERE option.name = '%s'",
                Option::class,
                $groupName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }

    public function deleteOption(string $groupName, string $optionName) {
        $group = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $groupName]);
        if ($group) {
            $dql = sprintf("DELETE %s value WHERE value.option = %s AND value.value = '%s'",
                Value::class,
                $group->getId(),
                $optionName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }
    public function flush() {
        $this->modelManager->flush();
    }
}