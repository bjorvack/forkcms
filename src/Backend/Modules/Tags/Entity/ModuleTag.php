<?php

namespace Backend\Modules\Tags\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="modules_tags")
 */
class ModuleTag
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $module;

    /**
     * @var Tag
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Tags\Entity\Tag", inversedBy="moduleTags")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     */
    private $tag;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="other_id")
     */
    private $other;

    public function __construct(
        string $module,
        Tag $tag,
        int $other
    ) {
        $this->module = $module;
        $this->tag = $tag;
        $this->other = $other;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function getOther(): int
    {
        return $this->other;
    }
}
