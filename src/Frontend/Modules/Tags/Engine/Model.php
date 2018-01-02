<?php

namespace Frontend\Modules\Tags\Engine;

use Backend\Modules\Tags\Entity\Tag;
use Backend\Modules\Tags\Repository\TagRepository;
use Common\Locale;
use Frontend\Core\Engine\Exception as FrontendException;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Navigation as FrontendNavigation;
use Frontend\Core\Language\Locale as FrontendLocale;

/**
 * In this file we store all generic functions that we will be using in the tags module
 */
class Model
{
    /**
     * Calls a method that has to be implemented though the tags interface
     *
     * @param string $module The module wherein to search.
     * @param string $class The class that should contain the method.
     * @param string $method The method to call.
     * @param mixed $parameter The parameters to pass.
     *
     * @throws FrontendException When FrontendTagsInterface is not correctly implemented to the module model
     *
     * @return mixed
     */
    public static function callFromInterface(string $module, string $class, string $method, $parameter = null)
    {
        // check to see if the interface is implemented
        if (in_array('Frontend\\Modules\\Tags\\Engine\\TagsInterface', class_implements($class))) {
            // return result
            return call_user_func([$class, $method], $parameter);
        }

        throw new FrontendException(
            'To use the tags module you need
            to implement the FrontendTagsInterface
            in the model of your module
            (' . $module . ').'
        );
    }

    private static function getRepository(): TagRepository
    {
        return FrontendModel::getContainer()->get('tags.repository.tag');
    }

    public static function get(string $url, Locale $locale = null): array
    {
        $repository = self::getRepository();

        return $repository->findByUrl($url, $locale);
    }

    /**
     * Fetch the list of all tags, ordered by their occurrence
     *
     * @return array
     */
    public static function getAll(): array
    {
        $repository = self::getRepository();

        return $repository->findByLanguage(
            FrontendLocale::frontendLanguage()
        );
    }

    public static function getMostUsed(int $limit): array
    {
        $repository = self::getRepository();

        return $repository->findByMostUsed(
            FrontendLocale::frontendLanguage(),
            $limit
        );
    }

    /**
     * @param string $module The module wherein the otherId occurs.
     * @param int $otherId The id of the item.
     * @param Locale|null $locale
     *
     * @return array
     */
    public static function getForItem(string $module, int $otherId, Locale $locale = null): array
    {
        $repository = self::getRepository();

        $return = [];

        // get tags
        $linkedTags = $repository->findItem(
            $module,
            $otherId,
            $locale ?? FrontendLocale::frontendLanguage()
        );

        // return
        if (empty($linkedTags)) {
            return $return;
        }

        // create link
        $tagLink = FrontendNavigation::getUrlForBlock('Tags', 'Detail');

        // loop tags
        foreach ($linkedTags as $row) {
            // add full URL
            $row['full_url'] = $tagLink . '/' . $row['url'];

            // add
            $return[] = $row;
        }

        // return
        return $return;
    }

    /**
     * Get tags for multiple items.
     *
     * @param string $module The module wherefore you want to retrieve the tags.
     * @param array $otherIds The ids for the items.
     * @param Locale|null $locale
     *
     * @return array
     */
    public static function getForMultipleItems(string $module, array $otherIds, Locale $locale = null): array
    {
        $repository = self::getRepository();

        // init var
        $return = [];

        // get tags
        $linkedTags = $repository->findItems(
            $module,
            $otherIds,
            $locale ?? FrontendLocale::frontendLanguage()
        );

        // return
        if (empty($linkedTags)) {
            return $return;
        }

        // create link
        $tagLink = FrontendNavigation::getUrlForBlock('Tags', 'Detail');

        // loop tags
        foreach ($linkedTags as $row) {
            // add full URL
            $row['full_url'] = $tagLink . '/' . $row['url'];

            // add
            $return[$row['other_id']][] = $row;
        }

        return $return;
    }

    public static function getIdByUrl(string $url): int
    {
        $repository = self::getRepository();

        return $repository->findIdByUrl($url);
    }

    public static function getModulesForTag(int $tagId): array
    {
        $repository = self::getRepository();

        return $repository->findModulesById($tagId);
    }

    public static function getName(int $tagId): string
    {
        $repository = self::getRepository();

        /** @var Tag $tag */
        $tag = $repository->find($tagId);

        return $tag->getTag();
    }

    /**
     * Get all related items
     *
     * @param int $id The id of the item in the source-module.
     * @param string $module The source module.
     * @param string $otherModule The module wherein the related items should appear.
     * @param int $limit The maximum of related items to grab.
     *
     * @return array
     */
    public static function getRelatedItemsByTags(int $id, string $module, string $otherModule, int $limit = 5): array
    {
        $repository = self::getRepository();

        return $repository->findRelatedItems($id, $module, $otherModule, $limit);
    }
}
