<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/9/19
 * Time: 2:37 PM
 */

namespace cennis\larelastic\Contracts;


interface LarelasticInterface
{
    /**
     * Returns json body params for must. document contents must match the $must params
     *
     * @param array
     *
     */
    public function must($data);

    /**
     * Returns json body params for must_not. document contents must NOT match the $must_not params
     *
     * @param array
     *
     */
    public function must_not($data);

    /**
     * Returns json body params for should. when chained with must it functions as an or.
     *
     * @param array
     *
     */
    public function should($data);


    /**
     * Returns json body params for filter. influences score when used with must
     *
     * @param array
     *
     */
    public function filter($data);

    /**
     * Returns json body params for sorting. Requires the field and order in the data array.
     *
     * @param array
     *
     */
    public function sort($data);

    /**
     * Returns json body params for nested sorting. Requires the field, nest_path, and order in the data array.
     *
     * @param array
     *
     */
    public function nested_sort($data);
}