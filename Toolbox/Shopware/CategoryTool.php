<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcCommons\Toolbox\Shopware;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;

class CategoryTool implements AugmentedObject
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    protected $childrenQuery;
    protected $categoryCache;
    protected $repository;

    public function removeEmptyCategories(array $ids)
    {
        $dql = 'SELECT c FROM Shopware\Models\Category\Category c WHERE c.id IN (:ids) AND c.blog = 0 '
            . 'AND c.articles IS EMPTY AND c.children IS EMPTY';
        $query = $this->modelManager->createQuery($dql)->setParameter('ids', $ids);
        $count = 0;
        while (true) {
            $emptyCategories = $query->getResult();
            if (empty($emptyCategories)) return $count;
            /** @var Category $category */
            foreach ($emptyCategories as $category) {
                $count++;
                $this->modelManager->remove($category);
                $this->log->debug('Empty category: ' . $category->getName());
            }
            $this->modelManager->flush();
            $this->modelManager->clear();
        }
    }

    public function removeEmptyProductCategories(Category $swCategory)
    {
        $ids = array_column($this->getRepository()->getFullChildrenList($swCategory->getId()), 'id');
        return $this->removeEmptyCategories($ids);
    }

    public function getChildPathes(Category $swCategory)
    {
        $children = $this->getRepository()->getFullChildrenList($swCategory->getId());
        $pathes = [];
        foreach ($children as $child)
        {
            $pathes[$child['id']] = $this->getRepository()->getPathById($child['id'], 'name', ' > ');
        }
        return array_map(function ($path) { return str_replace('Deutsch > ', '', $path);}, $pathes);
    }

    public function findCategoryPath(string $path, Category $root = null)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = $root ?? $repository->findOneBy(['parentId' => null]);
        $nodes = array_map('trim', explode('>', $path));
        foreach ($nodes as $name) {
            $child = $repository->findOneBy(['name' => $name, 'parentId' => $parent->getId()]);
            $parent = $child;
            if ($parent === null) break;
        }
        return $parent;
    }

    public function getChildCategory(Category $parent, string $name, bool $create = true)
    {
        $child = $this->getRepository()->findOneBy(['name' => $name, 'parentId' => $parent->getId()]);
        if ($child === null && $create === true) {
            $child = $this->createCategory($parent, $name);
        }
        return $child;
    }

    /**
     * Create and return a new sub-category for a given Shopware category.
     *
     * @param Category $parent
     * @param string $name
     * @return Category
     */
    public function createCategory(Category $parent, string $name)
    {
        $child = new Category();
        $this->modelManager->persist($child);
        $child->setName($name);
        $child->setParent($parent);
        $child->setHideFilter(true);
        $child->setHideSortings(true);
        $child->setChanged();
        if ($parent->getArticles()->count() > 0) {
            /** @var Article $article */
            foreach ($parent->getArticles() as $article) {
                $article->removeCategory($parent);
                $article->addCategory($child);
            }
            $parent->setChanged();
        }
        return $child;
    }

    /** Sort the subcategories of a given Category alphabetically by name
     *  No recursion.
     * @param Category $parent
     * @throws ORMException
     * @throws OptimisticLockException
     */

    public function sortSubCategories(Category $parent)
    {
        $children = $parent->getChildren();
        $array = [];
        /** @var Category $child */
        foreach ($children as $child) {
            $array[strtolower($child->getName())] = $child;
        }
        ksort($array);
        $i = 1;
        /** @var Category $child */
        foreach ($array as $child) {
            $child->setPosition($i);
            $i++;
        }
        $this->modelManager->flush();
    }

    protected function getRepository()
    {
        return $this->repository ?? $this->repository = $this->modelManager->getRepository(Category::class);
    }

}