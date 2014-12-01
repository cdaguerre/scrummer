<?php

namespace Scrummer\Scrum;

final class LabelMap
{
    public static $labels = array(
        'bug'         => 'red',
        'feature'     => 'green',
        'hotfix'      => 'orange',
        'enhancement' => 'blue',
        'release'     => 'yellow',
        'wontfix'     => 'purple',
    );

    public static function trelloToGithub($label)
    {
        $labels = array_flip(static::$labels);

        if (isset($labels[$label])) {
            return $labels[$label];
        }

        return 'bug';
    }

    public static function githubToTrello($label)
    {
        $labels = static::$labels;

        if (isset($labels[$label])) {
            return $labels[$label];
        }

        return 'red';
    }
}
