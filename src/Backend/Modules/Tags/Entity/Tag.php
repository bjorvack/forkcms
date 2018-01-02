<?php

namespace Backend\Modules\Tags\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Backend\Modules\Tags\Repository\TagRepository")
 * @ORM\Table(name="tags")
 */
class Tag
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $tag;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $url;

    /**
     * @ORM\OneToMany(targetEntity="Backend\Modules\Tags\Entity\ModuleTag", mappedBy="tag", cascade={"remove","persist"})
     */
    private $moduleTags;

    public function __construct(
        string $language,
        string $tag,
        int $number,
        string $url,
        ?Collection $moduleTags = null
    ) {
        $this->language = $language;
        $this->tag = $tag;
        $this->number = $number;
        $this->url = $url;
        $this->moduleTags = new ArrayCollection();

        if ($moduleTags) {
            $this->moduleTags = $moduleTags;
        }
    }

    public function update(
        string $language,
        string $tag,
        int $number,
        string $url,
        ?Collection $moduleTags = null
    ): void {
        $this->language = $language;
        $this->tag = $tag;
        $this->number = $number;
        $this->url = $url;

        if ($moduleTags) {
            $this->moduleTags = $moduleTags;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getModuleTags(): Collection
    {
        return $this->moduleTags;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'language' => $this->language,
            'tag' => $this->tag,
            'number' => $this->number,
            'url' => $this->url,
        ];
    }
}
