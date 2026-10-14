<?php
/**
 * 施工事例を種類で絞ったあと（/work-type/reform/ など）。
 * 一覧と同じ見た目にする（R-03）。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'parts/works-archive' );
get_footer();
