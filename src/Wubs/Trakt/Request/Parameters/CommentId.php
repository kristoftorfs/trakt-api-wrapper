<?php
/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 12/03/15
 * Time: 12:50
 */

namespace Wubs\Trakt\Request\Parameters;


class CommentId extends AbstractParameter implements Parameter
{
    public static function standard()
    {
        return new static(1);
    }
}