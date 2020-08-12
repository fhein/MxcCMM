<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;
use Throwable;

class ArticleTool implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var Repository */
    protected $articleRepository;

    public function setMainDetail(Detail $detail)
    {
        /** @var Article $article */
        $article = $detail->getArticle();
        if (! $article) return;

        $oldMainDetail = $article->getMainDetail();
        if ($oldMainDetail) {
            $oldMainDetail->setKind(2);
        }

        $article->setMainDetail($detail);
        $detail->setKind(1);
    }

    public function deleteDetail(Detail $detail)
    {
        $id = $detail->getId();
        $articleRepository = $this->getArticleRepository();
        $articleRepository->getRemoveImageQuery($id)->execute();

        $sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
        Shopware()->Db()->query($sql, [$id]);

        $articleRepository->getRemoveVariantTranslationsQuery($id)->execute();
        $articleRepository->getRemoveDetailQuery($id)->execute();
    }

    protected function getArticleRepository() {
        return $this->articleRepository ?? $this->articleRepository = $this->modelManager->getRepository(Article::class);
    }

    public static function getArticleMainDetailArray($articleId)
    {
        return Shopware()->Db()->fetchRow('
            SELECT * FROM 
              s_articles_details 
            LEFT JOIN 
              s_articles_attributes ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE 
              s_articles_details.articleID = ?
              AND s_articles_details.active = 1
              AND s_articles_details.kind = 1
            ', array($articleId)
        );
    }

    /**
     * @param $articleId
     * @return mixed
     */
    public static function getArticleDetailsArray($articleId) {
        return Shopware()->Db()->fetchAll('
            SELECT * FROM 
              s_articles_details 
            LEFT JOIN 
              s_articles_attributes ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE 
              s_articles_details.articleID = ?
            ', array($articleId)
        );
    }

    /**
     * @param $articleId
     * @return mixed
     */
    public static function getArticleSubDetailsArray($articleId) {

        return Shopware()->Db()->fetchAll('
            SELECT * FROM 
              s_articles_details 
            LEFT JOIN 
              s_articles_attributes ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE 
              s_articles_details.articleID = ?
              AND s_articles_details.active = 1
              AND s_articles_details.kind = 2
            ', array($articleId)
        );
    }

    public static function getArticleActiveDetailsArray($articleId)
    {
        return Shopware()->Db()->fetchAll('
            SELECT * FROM 
              s_articles_details 
            LEFT JOIN 
              s_articles_attributes ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE 
              s_articles_details.articleID = ?
              AND s_articles_details.active = 1
            ', array($articleId)
        );
    }

    public static function setArticleMainDetail(int $articleId, int $detailId) {
        try {
            // set all details to 2
            Shopware()->Db()->query("
                        UPDATE `s_articles_details` SET `kind` = 2
                        WHERE `articleID` = :articleId
                    ", ['articleId' => $articleId]);

            // set the first detail with stock to 1
            Shopware()->Db()->query("
                        UPDATE `s_articles_details` SET `kind` = 1
                        WHERE `articleID` = :articleId
                        AND `id` = :detailId
                    ", [
                'articleId' => $articleId,
                'detailId'  => $detailId
            ]);

            // update the article's main detail id
            Shopware()->Db()->query("
                        UPDATE `s_articles` SET `main_detail_id` = :detailId
                        WHERE `id` = :articleId
                    ", [
                'articleId' => $articleId,
                'detailId'  => $detailId
            ]);
        } catch (Throwable $e) {}
    }

    /**
     * Write an attribute value to all details of supplied article
     *
     * @param Article $article
     * @param string $attribute
     * @param $value
     * @throws DBALException
     */
    public static function setArticleAttribute(Article $article, string $attribute, $value)
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = sprintf("UPDATE s_articles_attributes attr 
                INNER JOIN s_articles_details d ON d.id = attr.articledetailsID
                SET attr.%s = :value 
                WHERE d.articleID = :articleId", $attribute);
        /** @var Statement $statement */
        $statement = $connection->prepare($sql);
        $statement->execute([
            'articleId' => $article->getId(),
            'value' => $value
        ]);
    }

    /**
     * Write an attribute value to supplied detail
     *
     * @param Detail $detail
     * @param string $attribute
     * @param $value
     * @throws DBALException
     */
    public static function setDetailAttribute(Detail $detail, string $attribute, $value)
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = sprintf("UPDATE s_articles_attributes attr 
                SET attr.%s = :value 
                WHERE attr.articledetailsID = :detailId", $attribute);
        /** @var Statement $statement */
        $statement = $connection->prepare($sql);
        $statement->execute([
            'detailId' => $detail->getId(),
            'value' => $value
        ]);
    }

    public static function getDetailAttributes(Detail $detail) {

        return Shopware()->Db()->fetchRow('
            SELECT * FROM 
              s_articles_attributes attr 
            WHERE 
                attr.articledetailsID = ?
            ', array($detail->getId())
        );
    }

    public static function getDetailAttribute(Detail $detail, string $attribute)
    {
        $attributes = self::getDetailAttributes($detail);
        return $attributes[$attribute];
    }
}
