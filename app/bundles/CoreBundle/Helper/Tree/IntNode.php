<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

use RecursiveIterator;

class IntNode implements NodeInterface
{
    /**
     * @var int
     */
    private $value;

    /**
     * @var NodeInterface|null
     */
    private $parent;

    /**
     * @var NodeInterface[]
     */
    private $children = [];

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(int $value, NodeInterface $parent = null)
    {
        $this->value  = $value;
        $this->parent = $parent;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getParent(): ?NodeInterface
    {
        return $this->parent;
    }

    public function setParent(NodeInterface $parent): void
    {
        $this->parent = $parent;
    }

    public function addChild(NodeInterface $child): void
    {
        $child->setParent($this);

        $this->children[] = $child;
    }

    public function getChildrenArray(): array
    {
        return $this->children;
    }

    public function getChildren(): RecursiveIterator
    {
        // return $this->children;
        // return $this->current()->getChildren();
        return $this->current();
    }

    public function hasChildren(): bool
    {
        return !empty($this->current()->getChildrenArray());
    }

    public function current(): NodeInterface
    {
        return $this->children[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }
}
