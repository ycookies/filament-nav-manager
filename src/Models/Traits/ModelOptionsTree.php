<?php

namespace Ycookies\FilamentNavManager\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

/**
 * @property string $parentColumn
 * @property string $titleColumn
 * @property string $orderColumn
 * @property string $depthColumn
 * @property string $defaultParentId
 * @property array $sortable
 */
trait ModelOptionsTree
{
    /**
     * Query builder instance for filtering nodes.
     *
     * @var Builder|null
     */
    protected $queryBuilder = null;

    /**
     * Get options for Select field in form.
     *
     * @param  \Closure|null  $closure
     * @param  string  $rootText
     * @param  string|null  $orderColumn
     * @return array
     */
    public static function selectOptions(\Closure $closure = null, $rootText = null, $orderColumn = null)
    {
        $rootText = $rootText ?: '顶级';

        $instance = new static();
        if ($orderColumn) {
            $instance->orderColumn = $orderColumn;
        }
        $options = $instance->withQuery($closure)->buildSelectOptions();

        return collect($options)->prepend($rootText, 0)->all();
    }

    /**
     * Apply query closure to filter nodes.
     *
     * @param  \Closure|null  $closure
     * @return $this
     */
    protected function withQuery(\Closure $closure = null)
    {
        if ($closure) {
            // Use buildSortQuery if available (from ModelTree trait), otherwise use query()
            if (method_exists(static::class, 'buildSortQuery')) {
                $this->queryBuilder = static::buildSortQuery();
            } else {
                $this->queryBuilder = static::query();
            }
            $closure($this->queryBuilder);
        }

        return $this;
    }

    /**
     * Get parent column name.
     *
     * @return string
     */
    protected function getParentColumn(): string
    {
        if (method_exists($this, 'determineParentColumnName')) {
            return $this->determineParentColumnName();
        }

        return $this->parentColumn ?? 'parent_id';
    }

    /**
     * Get title column name.
     *
     * @return string
     */
    protected function getTitleColumn(): string
    {
        if (method_exists($this, 'determineTitleColumnName')) {
            return $this->determineTitleColumnName();
        }

        return $this->titleColumn ?? 'name';
    }

    /**
     * Get order column name.
     *
     * @return string
     */
    protected function getOrderColumn(): string
    {
        if (method_exists($this, 'determineOrderColumnName')) {
            return $this->determineOrderColumnName();
        }

        return $this->orderColumn ?? 'sort_order';
    }

    /**
     * Get default parent ID.
     *
     * @return int
     */
    protected function getDefaultParentId(): int
    {
        if (method_exists($this, 'defaultParentKey')) {
            return static::defaultParentKey();
        }

        return $this->defaultParentId ?? 0;
    }

    /**
     * Get all nodes, optionally filtered by query builder.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAllNodes()
    {
        // Use static method if available (from ModelTree trait)
        if (method_exists(static::class, 'allNodes')) {
            $nodes = static::allNodes();
        } else {
            $nodes = static::query()->get();
        }

        // Apply query filters if closure was provided
        if ($this->queryBuilder) {
            $query = $this->queryBuilder;
            // Get the IDs that match the query
            $matchingIds = $query->pluck($this->getKeyName())->toArray();
            // Filter nodes to only include matching ones
            $nodes = $nodes->filter(function ($node) use ($matchingIds) {
                return in_array($node->getKey(), $matchingIds);
            });
        }

        return $nodes;
    }

    /**
     * Check if node has next sibling.
     *
     * @param  array  $nodes
     * @param  int  $parentId
     * @param  int  $currentIndex
     * @return bool
     */
    protected function hasNextSibling(array $nodes, int $parentId, int $currentIndex): bool
    {
        $parentColumn = $this->getParentColumn();

        for ($i = $currentIndex + 1; $i < count($nodes); $i++) {
            $node = $nodes[$i];
            $nodeParentId = $node[$parentColumn] ?? $this->getDefaultParentId();
            
            // Normalize parent ID (handle null, 0, -1)
            if ($nodeParentId === null) {
                $nodeParentId = $this->getDefaultParentId();
            }

            if ($nodeParentId == $parentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build options of select field in form.
     *
     * @param  array  $nodes
     * @param  int|null  $parentId
     * @param  string  $prefix
     * @param  string  $space
     * @return array
     */
    protected function buildSelectOptions(array $nodes = [], $parentId = null, $prefix = '', $space = "\xC2\xA0")
    {
        $d = '├─';
        $l = '└─';
        $v = '│';
        
        if ($parentId === null) {
            $parentId = $this->getDefaultParentId();
        }

        $options = [];

        if (empty($nodes)) {
            $nodes = $this->getAllNodes()->toArray();
        }

        $parentColumn = $this->getParentColumn();
        $titleColumn = $this->getTitleColumn();
        $keyName = $this->getKeyName();
        $orderColumn = $this->getOrderColumn();

        // Filter nodes by parent ID
        $children = [];
        $defaultParentId = $this->getDefaultParentId();
        
        foreach ($nodes as $index => $node) {
            $nodeParentId = $node[$parentColumn] ?? null;
            
            // Normalize parent ID (handle null, 0, -1 as root)
            if ($nodeParentId === null || $nodeParentId === 0 || $nodeParentId === -1) {
                $nodeParentId = $defaultParentId;
            }
            
            // Normalize the target parent ID for comparison
            $targetParentId = $parentId;
            if ($targetParentId === null || $targetParentId === 0 || $targetParentId === -1) {
                $targetParentId = $defaultParentId;
            }

            if ($nodeParentId == $targetParentId) {
                $children[] = [
                    'node' => $node,
                    'index' => $index,
                ];
            }
        }

        // Sort children by order column, then by key name if order is the same
        usort($children, function ($a, $b) use ($orderColumn, $keyName) {
            $orderA = $a['node'][$orderColumn] ?? 0;
            $orderB = $b['node'][$orderColumn] ?? 0;
            
            // First sort by order column
            if ($orderA != $orderB) {
                return $orderA <=> $orderB;
            }
            
            // If order is the same, sort by key name
            $keyA = $a['node'][$keyName] ?? 0;
            $keyB = $b['node'][$keyName] ?? 0;
            return $keyA <=> $keyB;
        });

        // Process each child
        foreach ($children as $childIndex => $childData) {
            $node = $childData['node'];
            $index = $childData['index'];
            $nodeId = $node[$keyName];

            // Determine if this is the last sibling
            $isLastSibling = ($childIndex === count($children) - 1);
            
            // Build current prefix and display text
            if (empty($prefix)) {
                // Root level - no prefix, just the title
                $displayText = $node[$titleColumn];
            } else {
                // Child level - add tree character
                $treeChar = $isLastSibling ? $l : $d;
                $displayText = $prefix . $treeChar . $space . $node[$titleColumn];
            }

            // Build prefix for children
            if (empty($prefix)) {
                // First level children - start with spaces
                $childrenPrefix = str_repeat($space, 2);
            } else {
                // Deeper levels - extend the prefix
                if ($isLastSibling) {
                    // Last child - replace tree char with spaces, add more spaces
                    $childrenPrefix = str_replace([$d, $l], str_repeat($space, 2), $prefix) . str_repeat($space, 2);
                } else {
                    // Not last child - replace tree char with vertical line, add spaces
                    $childrenPrefix = str_replace($d, $v . $space, $prefix) . str_repeat($space, 2);
                }
            }

            // Recursively build children options
            $childrenOptions = $this->buildSelectOptions($nodes, $nodeId, $childrenPrefix, $space);

            // Add current node to options
            $options[$nodeId] = $displayText;

            // Add children options
            if ($childrenOptions) {
                $options += $childrenOptions;
            }
        }

        return $options;
    }
}
