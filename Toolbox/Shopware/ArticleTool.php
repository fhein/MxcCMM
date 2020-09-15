<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;
use Throwable;

class ArticleTool implements AugmentedObject
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var Repository */
    protected $articleRepository;

    public function deleteDetail(Detail $detail)
    {
        $id = $detail->getId();
        $articleRepository = $this->getArticleRepository();

        $sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
        Shopware()->Db()->query($sql, [$id]);

        $articleRepository->getRemoveVariantTranslationsQuery($id)->execute();
        $articleRepository->getRemoveDetailQuery($id)->execute();
        $articleRepository->getRemoveImageQuery($id)->execute();
    }

    public static function getArticleMainDetailArray($articleId)
    {
        return Shopware()->Db()->fetchRow('
            SELECT * FROM s_articles_details 
            LEFT JOIN s_articles_attributes ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE s_articles_details.articleID = ? AND s_articles_details.active = 1 AND s_articles_details.kind = 1',
            array($articleId)
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

    public static function setMainDetail(int $articleId, int $detailId) {
        try {
            // set all details to 2 -> not main detail
            Shopware()->Db()->query(
                "UPDATE `s_articles_details` SET `kind` = 2
                     WHERE `articleID` = :articleId",
                ['articleId' => $articleId]
            );
            // set all details to 1 -> main detail
            Shopware()->Db()->query(
                "UPDATE `s_articles_details` SET `kind` = 1
                    WHERE `articleID` = :articleId
                    AND `id` = :detailId",
                [
                    'articleId' => $articleId,
                    'detailId'  => $detailId
                ]
            );

            // update the article's main detail id
            Shopware()->Db()->query("
                UPDATE `s_articles` SET `main_detail_id` = :detailId
                WHERE `id` = :articleId",
                [
                    'articleId' => $articleId,
                    'detailId'  => $detailId
                ]
            );
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
        $sql = sprintf(
            "UPDATE s_articles_attributes attr 
            INNER JOIN s_articles_details d ON d.id = attr.articledetailsID
            SET attr.%s = :value 
            WHERE d.articleID = :articleId",
            $attribute
        );
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
     * @param Detail|int $detail
     * @param string $attribute
     * @param $value
     * @throws DBALException
     */
    public static function setDetailAttribute($detail, string $attribute, $value)
    {
        $detailId = $detail instanceof Detail ? $detail->getId() : $detail;
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = sprintf(
                "UPDATE s_articles_attributes attr 
                SET attr.%s = :value 
                WHERE attr.articledetailsID = :detailId",
                $attribute
        );
        /** @var Statement $statement */
        $statement = $connection->prepare($sql);
        $statement->execute([ 'detailId' => $detailId, 'value' => $value ]);
    }

    public static function getDetailAttributes($detail) {
        $detailId = $detail instanceof Detail ? $detail->getId() : $detail;
        return Shopware()->Db()->fetchRow(
            'SELECT * FROM s_articles_attributes attr WHERE attr.articledetailsID = ?', [$detailId]
        );
    }

    public static function getDetailAttribute(Detail $detail, string $attribute)
    {
        $attributes = self::getDetailAttributes($detail);
        return $attributes[$attribute];
    }

    // Deletes a Shopware article and all related data
    // Important: This function does not cover additional tables as introduced by SwagBundle for example
    public static function deleteArticle($article)
    {
        if (! $article instanceof Article && ! is_int($article)) return false;
        $id = is_int($article) ? $article : $article->getId();

        $sql = 'DELETE aimr.*, aim.*, acta.*, actpa.*, actp.*, act.*, acor.*, av.*, atr.*, atop.*, ASiro.*, ASi.*, ar.*, 
                       apa.*, ap.*, an.*, aina.*, ain.*, aia.*, ai.*, aesds.*, aesda.*, aesd.*, adoa.*, ado.*, acs.*, 
                       acr.*, ac.*, acu.*, aa.*, ad.*, bro.*, a.*
                FROM s_articles AS a 
                LEFT JOIN s_articles_also_bought_ro AS bro ON a.id = bro.article_id 
                LEFT JOIN s_articles_details AS ad ON a.id = ad.articleID 
                LEFT JOIN s_articles_attributes AS aa ON ad.id = aa.articledetailsID 
                LEFT JOIN s_articles_avoid_customergroups AS acu ON a.id = acu.articleID 
                LEFT JOIN s_articles_categories AS ac ON a.id = ac.articleID 
                LEFT JOIN s_articles_categories_ro AS acr ON a.id = acr.articleID 
                LEFT JOIN s_articles_categories_seo acs ON a.id = acs.article_id 
                LEFT JOIN s_articles_downloads AS ado ON a.id = ado.articleID 
                LEFT JOIN s_articles_downloads_attributes AS adoa ON ado.id = adoa.downloadID 
                LEFT JOIN s_articles_esd AS aesd ON a.id = aesd.articleID 
                LEFT JOIN s_articles_esd_attributes AS aesda ON aesd.id = aesda.esdID 
                LEFT JOIN s_articles_esd_serials AS aesds ON aesd.id = aesds.esdID 
                LEFT JOIN s_articles_img AS ai ON a.id = ai.articleID 
                LEFT JOIN s_articles_img_attributes AS aia ON ai.id = aia.imageID 
                LEFT JOIN s_articles_information AS ain ON a.id = ain.articleID 
                LEFT JOIN s_articles_information_attributes AS aina ON ain.id = aina.informatiONID 
                LEFT JOIN s_articles_notification AS an ON ad.ordernumber = an.ordernumber 
                LEFT JOIN s_articles_prices AS ap ON a.id = ap.articleID 
                LEFT JOIN s_articles_prices_attributes AS apa ON ap.id = apa.priceID 
                LEFT JOIN s_articles_relationships AS ar ON a.id = ar.articleID 
                LEFT JOIN s_articles_similar AS ASi ON a.id = ASi.articleID 
                LEFT JOIN s_articles_similar_shown_ro AS ASiro ON a.id = ASiro.article_id 
                LEFT JOIN s_articles_top_seller_ro AS atop ON a.id = atop.article_id 
                LEFT JOIN s_articles_translations AS atr ON a.id = atr.articleID 
                LEFT JOIN s_articles_vote AS av ON a.id = av.articleID 
                LEFT JOIN s_article_configurator_option_relations AS acor ON ad.id = acor.article_id 
                LEFT JOIN s_article_configurator_templates AS act ON a.id = act.article_id 
                LEFT JOIN s_article_configurator_template_prices AS actp ON act.id = actp.template_id 
                LEFT JOIN s_article_configurator_template_prices_attributes AS actpa ON actp.id = actpa.template_price_id 
                LEFT JOIN s_article_configurator_templates_attributes AS acta ON act.id = acta.template_id 
                LEFT JOIN s_article_img_mappings AS aim ON ai.id = aim.image_id 
                LEFT JOIN s_article_img_mapping_rules AS aimr ON aim.id = aimr.mapping_id
                WHERE a.id = ?';
        return Shopware()->Db()->query($sql, [$id]);
    }

    protected function getArticleRepository() {
        return $this->articleRepository ?? $this->articleRepository = $this->modelManager->getRepository(Article::class);
    }
}
