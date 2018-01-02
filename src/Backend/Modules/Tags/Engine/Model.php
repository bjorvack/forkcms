<?php

namespace Backend\Modules\Tags\Engine;

use Backend\Modules\Tags\Entity\Tag;
use Backend\Modules\Tags\Repository\TagRepository;
use Common\Uri as CommonUri;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;

/**
 * In this file we store all generic functions that we will be using in the TagsModule
 */
class Model
{
    const QUERY_DATAGRID_BROWSE =
        'SELECT i.id, i.tag, i.number AS num_tags
         FROM tags AS i
         WHERE i.language = ?
         GROUP BY i.id';

    private static function getRepository(): TagRepository
    {
        return BackendModel::getContainer()->get('tags.repository.tag');
    }

    /**
     * Delete one or more tags.
     *
     * @param int|int[] $ids The ids to delete.
     */
    public static function delete($ids): void
    {
        $repository = self::getRepository();
        $repository->removeByIds((array) $ids);
    }

    public static function exists(int $id): bool
    {
        $repository = self::getRepository();

        return $repository->find($id) instanceof Tag;
    }

    public static function existsTag(string $tag): bool
    {
        $repository = self::getRepository();

        return count($repository->findByTag($tag)) > 0;
    }

    public static function get(int $id): array
    {
        $repository = self::getRepository();

        return $repository->findById($id, 'name');
    }

    public static function getAll(string $language = null): array
    {
        $repository = self::getRepository();

        return $repository->findByLanguage($language ?? BL::getWorkingLanguage(), 'name');
    }

    public static function getTagNames(string $language = null): array
    {
        $repository = self::getRepository();

        return $repository->findByLanguage($language ?? BL::getWorkingLanguage());
    }

    /**
     * Get tags that start with the given string
     *
     * @param string $term     The searchstring.
     * @param string $language The language to use, if not provided use the working language.
     *
     * @return array
     */
    public static function getStartsWith(string $term, string $language = null): array
    {
        $repository = self::getRepository();

        return $repository->findByStart($term, $language ?? BL::getWorkingLanguage(), 'name');
    }

    /**
     * Get tags for an item.
     *
     * @param string $module   the module wherein will be searched
     * @param int    $otherId  the id of the record
     * @param string $type     the type of the returnvalue, possible values are: array, string (tags will be joined by ,)
     * @param string $language the language to use, if not provided the working language will be used
     *
     * @return mixed
     */
    public static function getTags(string $module, int $otherId, string $type = 'string', string $language = null)
    {
        $repository = self::getRepository();

        return $repository->findByModule($module, $otherId, $type, $language ?? BL::getWorkingLanguage());
    }

    /**
     * Get a unique URL for a tag.
     *
     * @param string   $url the URL to use as a base
     * @param int|null $id  the ID to ignore
     *
     * @return string
     */
    public static function getUrl(string $url, int $id = null): string
    {
        $repository = self::getRepository();
        $url = CommonUri::getUrl($url);
        $language = BL::getWorkingLanguage();

        return $repository->getUrl($url, $id, $language);
    }

    /**
     * Insert a new tag.
     *
     * @param string $tag      the data for the tag
     * @param string $language the language wherein the tag will be inserted,
     *                         if not provided the workinglanguage will be used
     *
     * @return int
     */
    public static function insert(string $tag, string $language = null): int
    {
        $tag = new Tag(
            $language ?? BL::getWorkingLanguage(),
            $tag,
            0,
            self::getUrl($tag)
        );

        $repository = self::getRepository();
        $repository->save($tag);

        return $tag->getId();
    }

    /**
     * Save the tags.
     *
     * @param int         $otherId  the id of the item to tag
     * @param mixed       $tags     the tags for the item
     * @param string      $module   the module wherein the item is located
     * @param string|null $language the language wherein the tags will be inserted,
     *                              if not provided the workinglanguage will be used
     */
    public static function saveTags(int $otherId, $tags, string $module, string $language = null)
    {
        $repository = self::getRepository();

        // redefine the tags as an array
        if (!is_array($tags)) {
            $tags = (array) explode(',', (string) $tags);
        }

        // make sure the list of tags contains only unique and non-empty elements
        $tags = array_filter(array_unique($tags));

        $repository->saveTags($otherId, $tags, $module, $language ?? BL::getWorkingLanguage());
    }

    /**
     * Update a tag
     * Remark: $tag['id'] should be available.
     *
     * @param array $tag the new data for the tag
     *
     * @return int
     */
    public static function update(array $tag): int
    {
        $repository = self::getRepository();

        /** @var Tag $tagEntity */
        $tagEntity = $repository->find($tag['id']);
        $tagEntity->update(
            key_exists('language', $tag) ? $tag['language'] : $tagEntity->getLanguage(),
            key_exists('tag', $tag) ? $tag['tag'] : $tagEntity->getTag(),
            key_exists('number', $tag) ? $tag['number'] : $tagEntity->getNumber(),
            key_exists('url', $tag) ? $tag['url'] : $tagEntity->getUrl()
        );

        $repository->save($tagEntity);

        return $tagEntity->getId();
    }
}
