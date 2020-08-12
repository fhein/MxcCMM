<?php

namespace MxcCommons\Toolbox\Arrays;

class ArrayTool
{
    /**
     * Build the cartesian product of an array of arrays
     *
     * @param array $input
     * @return array
     */
    public static function cartesianProduct(array $input)
    {
        $result = array();
        foreach ($input as $key => $values) {

            if (empty($values)) {
                continue;
            }

            if (empty($result)) {
                foreach ($values as $value) {
                    $result[] = array($key => $value);
                }
                continue;
            }
            $append = array();

            foreach ($result as &$product) {
                $product[$key] = array_shift($values);
                $copy = $product;
                foreach ($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }
                array_unshift($values, $product[$key]);
            }

            $result = array_merge($result, $append);
        }

        return $result;
    }

    public static function ksort_recursive(array &$tree, int $flags = SORT_NATURAL | SORT_FLAG_CASE)
    {
        foreach ($tree as &$node) {
            if (is_array($node)) {
                if (self::ksort_recursive($node, $flags) === false) return false;
            }
        }
        return ksort($tree, $flags);
    }

    public static function uksort_recursive(array &$tree, callable $compare)
    {
        foreach ($tree as &$node) {
            if (is_array($node)) {
                if (self::uksort_recursive($node, $compare) === false) return false;
            }
        }
        return uksort($tree, $compare);
    }

    public static function krsort_recursive(array &$tree, int $flags = SORT_NATURAL | SORT_FLAG_CASE)
    {
        if (krsort($tree, $flags) === false) return false;
        foreach ($tree as &$node) {
            if (is_array($node)) {
                if (self::krsort_recursive($node, $flags) === false) return false;
            }
        }
        return krsort($tree, $flags);
    }


}