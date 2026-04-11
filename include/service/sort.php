<?php
/**
 * Service: Sort
 */

class Sort {
    static function formatSortTitle($title, $sortName) {

        $site_title = Option::get('site_title');
        $blogname = Option::get('blogname');

        if (empty($site_title)) {
            $site_title = $blogname;
        }
        if(empty($title)){
            return $sortName;
        }
        return strtr($title, [
            '{{site_title}}' => $site_title,
            '{{site_name}}'  => $blogname,
            '{{sort_name}}'  => $sortName
        ]);
    }
}
