<?php

namespace Backend\Modules\Tags\Repository;

use Backend\Core\Engine\Model;
use Backend\Modules\Tags\Entity\ModuleTag;
use Backend\Modules\Tags\Entity\Tag;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;
use Common\Locale;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class TagRepository extends EntityRepository
{
    public function save(Tag $tag): void
    {
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
    }

    public function saveTags(int $otherId, array $tags, string $module, string $language): void
    {
        // Get current tags for item
        $currentTags = $this->findByModule($module, $otherId, 'array', $language);

        if (!empty($currentTags)) {
            // Remove old links
            $this->removeOldLinks($otherId, $module, $currentTags);
        }

        if (!empty($tags)) {
            $tags = $this->cleanupTags($tags);

            $existingTags = $this->findExistingTags($tags, $language);
            $newTags = array_diff($tags, $existingTags);

            $this->createNewTags($newTags, $language, $module, $otherId);
            $this->addModuleTags($existingTags, $language, $module, $otherId);
            $this->upCountTags($newTags, $language);
        }

        // add to search index
        BackendSearchModel::saveIndex(
            $module,
            $otherId,
            ['tags' => implode(' ', $tags)],
            $language
        );

        $this->lowerCountTags(array_filter($currentTags, $tags), $language);
        $this->removeZeroCountTags();
    }

    private function removeZeroCountTags(): void
    {
        $this->getEntityManager()->createQuery(
            'delete from Backend\Modules\Tags\Entity\Tag t WHERE t.number = 0'
        )->execute();
    }

    private function lowerCountTags(array $tags, string $language): void
    {
        $this->getEntityManager()
            ->createQuery(
                'UPDATE '.Tag::class.' t '.
                'SET t.number = t.number - 1 '.
                'WHERE t.tag IN (:tags) '.
                'AND t.language = :language'
            )
            ->setParameters([
                'tags' => $tags,
                'language' => $language,
            ])
            ->execute();
    }

    private function upCountTags(array $tags, string $language): void
    {
        $this->getEntityManager()
            ->createQuery(
                'UPDATE '.Tag::class.' t '.
                'SET t.number = t.number + 1 '.
                'WHERE t.tag IN (:tags) '.
                'AND t.language = :language'
            )
            ->setParameters([
                'tags' => $tags,
                'language' => $language,
            ])
            ->execute();
    }

    private function addModuleTags(array $existingTags, string $language, string $module, int $otherId)
    {
        foreach ($existingTags as $existingTag) {
            /** @var Tag $tag */
            $tag = $this->findOneBy(['tag' => $existingTag, 'language' => $language]);
            $moduleTag = new ModuleTag($module, $tag, $otherId);
            $this->getEntityManager()->persist($moduleTag);
        }
        $this->getEntityManager()->flush();
    }

    private function createNewTags(array $tags, string $language, string $module, int $other): void
    {
        foreach ($tags as $tag) {
            $tag = new Tag(
                $language,
                $tag,
                1,
                $this->getUrl($tag, null, $language)
            );

            $this->getEntityManager()->persist($tag);

            $moduleTag = new ModuleTag($module, $tag, $other);
            $this->getEntityManager()->persist($moduleTag);
        }

        $this->getEntityManager()->flush();
    }

    private function findExistingTags(array $tags, string $language): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as tag')
            ->from(Tag::class, 't')
            ->where('t.language = :language')
            ->andWhere('t.tag IN (:tags)')
            ->setParameters([
                'language' => $language,
                'tags' => $tags,
            ])
            ->getQuery()
            ->getResult();

        array_walk($results, function (&$result) {
            $result = $result['tag'];
        });

        return $results;
    }

    private function removeOldLinks(int $otherId, string $module, array $currentTags): void
    {
        $this->getEntityManager()->createQuery(
            'delete from Backend\Modules\Tags\Entity\ModuleTag mt '.
            'inner join Backend\Modules\Tags\Entity\Tag t on mt.tag_id = t.id'.
            'WHERE mt.module = :module '.
            'AND mt.other_id = :otherId '.
            'AND t.tag IN (:tags)'
        )
            ->setParameters([
                'otherId' => $otherId,
                'module' => $module,
                'tags' => $currentTags,
            ])
            ->execute();
    }

    private function cleanupTags(array $tags): array
    {
        // Cleanup Tags
        array_walk($tags, function (&$tag) {
            $tag = mb_strtolower(trim($tag));
        });

        return array_filter($tags);
    }

    public function removeByIds(array $ids): void
    {
        $this->getEntityManager()->createQuery(
            'delete from Backend\Modules\Tags\Entity\Tag t WHERE t.id IN (:ids)'
        )
            ->setParameter('ids', $ids)
            ->execute();

        $this->getEntityManager()->createQuery(
            'delete from Backend\Modules\Tags\Entity\ModuleTag t WHERE t.tag IN (:ids)'
        )
            ->setParameter('ids', $ids)
            ->execute();
    }

    public function findById(int $id, string $alias = 'tag'): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as '.$alias)
            ->from(Tag::class, 't')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        array_walk($results, function (&$result) use ($alias) {
            $result = $result[$alias];
        });

        return $results;
    }

    public function findTagByLanguage(string $language, string $alias = 'tag'): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as '.$alias)
            ->from(Tag::class, 't')
            ->where('t.language = :language')
            ->setParameter('language', $language)
            ->getQuery()
            ->getResult();

        array_walk($results, function (&$result) use ($alias) {
            $result = $result[$alias];
        });

        return $results;
    }

    public function findByStart(string $term, string $language, string $alias = 'tag'): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as '.$alias)
            ->from(Tag::class, 't')
            ->where('t.language = :language')
            ->andWhere('t.tag LIKE :term')
            ->setParameters([
                'language' => $language,
                'term' => $term.'%',
            ])
            ->getQuery()
            ->getResult();

        array_walk($results, function (&$result) use ($alias) {
            $result = $result[$alias];
        });

        return $results;
    }

    public function findByModule(string $module, int $otherId, string $returnType, string $language): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as tag')
            ->from(Tag::class, 't')
            ->innerJoin('t.moduleTags', 'mt')
            ->where('mt.module = :module')
            ->andWhere('mt.other = :otherId')
            ->andWhere('t.language = :language')
            ->setParameters([
                'module' => $module,
                'otherId' => $otherId,
                'language' => $language,
            ])
            ->getQuery()
            ->getScalarResult();

        array_walk($results, function (&$result) {
            $result = $result['tag'];
        });

        switch ($returnType) {
            case 'array':
                return $results;
                break;
            case 'string':
            default:
                return implode(',', $results);
                break;
        }
    }

    public function urlExists(string $url, ?int $ignoreId, string $language): bool
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as tag')
            ->from(Tag::class, 't')
            ->where('t.url = :url')
            ->andWhere('t.language = :language')
            ->setParameters([
                'url' => $url,
                'language' => $language,
            ]);

        if (null !== $ignoreId) {
            $results
                ->andWhere('t.id != :id')
                ->setParameter('id', $ignoreId);
        }

        $results->getQuery()->getScalarResult();

        return count($results) > 0;
    }

    public function getUrl(string $url, ?int $id, string $language): string
    {
        if ($this->urlExists($url, $id, $language)) {
            return $this->getUrl(
                Model::addNumber($url),
                $id,
                $language
            );
        }

        return $url;
    }

    public function findByLanguage(Locale $locale): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as name, t.url, t.number')
            ->from(Tag::class, 't')
            ->where('t.language = :language')
            ->setParameter('language', $locale->getLocale())
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findByMostUsed(Locale $locale, int $limit): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as name, t.url, t.number')
            ->from(Tag::class, 't')
            ->where('t.language = :language')
            ->andWhere('t.number > 0')
            ->setParameter('language', $locale->getLocale())
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findByUrl(string $url, Locale $locale): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.id, t.language, t.tag as name, t.number, t.url')
            ->from(Tag::class, 't')
            ->where('t.url = :url')
            ->andWhere('t.language = :language')
            ->setParameters([
                'url' => $url,
                'language' => $locale->getLocale(),
            ])
            ->orderBy('name', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return $results;
    }

    public function findItem(string $module, int $otherId, Locale $locale): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as tag, t.url')
            ->from(Tag::class, 't')
            ->innerJoin('t.moduleTags', 'mt')
            ->where('mt.module = :module')
            ->andWhere('mt.other = :otherId')
            ->andWhere('t.language = :language')
            ->setParameters([
                'module' => $module,
                'otherId' => $otherId,
                'language' => $locale->getLocale(),
            ])
            ->getQuery()
            ->getScalarResult();
    }

    public function findItems(string $module, array $otherIds, Locale $locale): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.tag as tag, t.url')
            ->from(Tag::class, 't')
            ->innerJoin('t.moduleTags', 'mt')
            ->where('mt.module = :module')
            ->andWhere('mt.other IN (:otherId)')
            ->andWhere('t.language = :language')
            ->setParameters([
                'module' => $module,
                'otherId' => $otherIds,
                'language' => $locale->getLocale(),
            ])
            ->getQuery()
            ->getScalarResult();
    }

    public function findIdByUrl(string $url): int
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.url')
            ->from(Tag::class, 't')
            ->where('t.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getScalarResult();

        array_walk($results, function (&$result) {
            $result = $result['url'];
        });

        return intval($results[0]);
    }

    public function findModulesById(int $id): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('mt.module')
            ->from(ModuleTag::class, 'mt')
            ->where('mt.tag_id = :tag')
            ->setParameter('tag', $id)
            ->getQuery()
            ->getScalarResult();

        array_walk($results, function (&$result) {
            $result = $result['module'];
        });

        return $results;
    }

    public function findRelatedItems(int $otherId, string $module, string $otherModule, int $limit): array
    {
        $results = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('mt2.other_id')
            ->from(ModuleTag::class, 'mt')
            ->innerJoin('modules_tags', 'mt2', Join::ON, 'mt.tag_id = mt2.tag_id')
            ->where('mt.other_id = :otherId')
            ->andWhere('mt.module = :module')
            ->andWhere('mt2.module = :otherModule')
            ->andWhere('(mt2.module != mt.module OR mt2.other_id != mt.other_id)')
            ->setParameters([
                'otherModule' => $otherModule,
                'module' => $module,
                'otherId' => $otherId,
            ])
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        array_walk($results, function (&$result) {
            $result = $result['other_id'];
        });

        return $results;
    }
}
